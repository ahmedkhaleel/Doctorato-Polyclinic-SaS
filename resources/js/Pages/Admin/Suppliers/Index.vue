<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, onMounted } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { useConfirm } from '@/Composables/useConfirm.js'

defineOptions({ layout: AdminLayout })

const { confirm } = useConfirm()

const props = defineProps({ suppliers: Object, filters: Object })

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl')

const mounted = ref(false)
onMounted(() => setTimeout(() => { mounted.value = true }, 50))

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const showModal = ref(false)
const editing = ref(null)
const processing = ref(false)
const form = ref({ name_ar: '', name_en: '', code: '', phone: '', email: '', contact_person: '', address: '', tax_number: '', payment_terms: '', lead_time_days: null, notes: '', is_active: true })

let debounce = null
function applyFilters() {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
        router.get('/admin/suppliers', { search: search.value || undefined, status: statusFilter.value || undefined }, { preserveState: true, replace: true })
    }, 300)
}

function openCreate() { editing.value = null; form.value = { name_ar: '', name_en: '', code: '', phone: '', email: '', contact_person: '', address: '', tax_number: '', payment_terms: '', lead_time_days: null, notes: '', is_active: true }; showModal.value = true }
function openEdit(s) { editing.value = s; form.value = { ...s }; showModal.value = true }

function submit() {
    processing.value = true
    const url = editing.value ? `/admin/suppliers/${editing.value.id}/update` : '/admin/suppliers'
    router.post(url, form.value, { onSuccess: () => { showModal.value = false }, onFinish: () => { processing.value = false } })
}

function remove(s) {
    confirm(isRtl.value ? 'هل أنت متأكد من حذف هذا المورد؟' : 'Are you sure you want to delete this supplier?', () => {
        router.post(`/admin/suppliers/${s.id}/delete`)
    })
}

function displayName(s) {
    return isRtl.value ? (s.name_ar || s.name_en) : (s.name_en || s.name_ar)
}

const totalSuppliers = computed(() => props.suppliers?.total || props.suppliers?.data?.length || 0)
const activeCount = computed(() => props.suppliers?.data?.filter(s => s.is_active)?.length || 0)
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#1B365D] p-4 md:p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-slate-400/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-[#1B365D]/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;40&quot; height=&quot;40&quot; viewBox=&quot;0 0 40 40&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;%23fff&quot;%3E%3Ccircle cx=&quot;1&quot; cy=&quot;1&quot; r=&quot;1&quot;/%3E%3C/g%3E%3C/svg%3E')"></div>
            <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-slate-400/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                        </div>
                        <p class="text-slate-300 text-xs font-semibold tracking-wider uppercase">{{ isRtl ? 'إدارة المخزون' : 'Inventory' }}</p>
                    </div>
                    <h1 class="text-xl md:text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'الموردين' : 'Suppliers' }}</h1>
                    <p class="text-slate-200/70 text-sm mt-1">{{ isRtl ? 'إدارة الموردين ومعلومات التواصل' : 'Manage suppliers and contact information' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-5 py-3.5 border border-white/10 text-center min-w-[80px]">
                        <p class="text-xl md:text-2xl font-bold text-white leading-none tabular-nums">{{ totalSuppliers }}</p>
                        <p class="text-[10px] text-slate-300 mt-1 font-medium uppercase tracking-wide">{{ isRtl ? 'مورد' : 'Total' }}</p>
                    </div>
                    <button @click="openCreate"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 backdrop-blur-sm text-white text-sm font-semibold rounded-xl border border-white/20 hover:bg-white/20 transition-all duration-200 shadow-lg shadow-black/10"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ isRtl ? 'إضافة مورد' : 'Add Supplier' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
        >
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input v-model="search" @input="applyFilters" type="text" :placeholder="isRtl ? 'بحث بالاسم أو الكود...' : 'Search by name or code...'" class="doctorato-input w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                </div>
                <select v-model="statusFilter" @change="applyFilters()" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] min-w-[130px]">
                    <option value="">{{ isRtl ? 'كل الحالات' : 'All Status' }}</option>
                    <option value="active">{{ isRtl ? 'نشط' : 'Active' }}</option>
                    <option value="inactive">{{ isRtl ? 'غير نشط' : 'Inactive' }}</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="px-5 py-3.5 text-start text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المورد' : 'Supplier' }}</th>
                            <th class="px-5 py-3.5 text-start text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'التواصل' : 'Contact' }}</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'مدة التوصيل' : 'Lead Time' }}</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المنتجات' : 'Products' }}</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'شروط الدفع' : 'Terms' }}</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="s in suppliers.data" :key="s.id" class="hover:bg-gray-50/50 transition-colors duration-150">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-slate-100 to-slate-50 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ displayName(s) }}</p>
                                        <p v-if="s.code" class="text-[10px] text-gray-400 font-mono mt-0.5">{{ s.code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div v-if="s.phone" class="text-xs text-gray-600 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    <span dir="ltr">{{ s.phone }}</span>
                                </div>
                                <div v-if="s.email" class="text-[10px] text-gray-400 mt-0.5 truncate max-w-[180px]">{{ s.email }}</div>
                                <div v-if="s.contact_person" class="text-[10px] text-gray-400 mt-0.5">{{ s.contact_person }}</div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span v-if="s.lead_time_days" class="text-xs font-medium text-gray-600">{{ s.lead_time_days }} {{ isRtl ? 'يوم' : 'days' }}</span>
                                <span v-else class="text-xs text-gray-300">-</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 rounded-full bg-slate-50 text-[#1B365D] text-xs font-bold tabular-nums">{{ s.supplies_count || 0 }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-xs text-gray-500">{{ s.payment_terms || '-' }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span :class="s.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="s.is_active ? 'bg-emerald-500' : 'bg-red-400'"></span>
                                    {{ s.is_active ? (isRtl ? 'نشط' : 'Active') : (isRtl ? 'معطل' : 'Inactive') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-0.5">
                                    <button @click="openEdit(s)" class="p-2 text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 rounded-lg transition-all" :title="isRtl ? 'تعديل' : 'Edit'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="remove(s)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" :title="isRtl ? 'حذف' : 'Delete'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!suppliers.data?.length" class="py-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا يوجد موردين' : 'No suppliers found' }}</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="suppliers.links && suppliers.last_page > 1" class="flex justify-center gap-1">
            <template v-for="link in suppliers.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" v-html="link.label" preserve-state
                    class="px-3.5 py-2 rounded-lg text-xs font-medium transition-all duration-200"
                    :class="link.active ? 'bg-[#1B365D] text-white shadow-sm shadow-slate-200' : 'text-gray-500 bg-white border border-gray-100 hover:bg-gray-50 hover:border-gray-200'"
                />
                <span v-else v-html="link.label" class="px-3.5 py-2 text-xs text-gray-300" />
            </template>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModal = false" />
                    <Transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 scale-90 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showModal" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                            <!-- Modal Header -->
                            <div class="sticky top-0 bg-white border-b border-gray-100 px-4 md:px-6 py-4 rounded-t-2xl z-10">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-bold text-gray-900">{{ editing ? (isRtl ? 'تعديل المورد' : 'Edit Supplier') : (isRtl ? 'إضافة مورد جديد' : 'Add New Supplier') }}</h2>
                                        <p class="text-xs text-gray-500">{{ isRtl ? 'أدخل بيانات المورد' : 'Enter supplier details' }}</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal Body -->
                            <form @submit.prevent="submit" class="p-4 md:p-6 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'الاسم بالعربي' : 'Name (AR)' }} <span class="text-red-400">*</span></label>
                                        <input v-model="form.name_ar" required dir="rtl" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'الاسم بالإنجليزي' : 'Name (EN)' }} <span class="text-red-400">*</span></label>
                                        <input v-model="form.name_en" required class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'الرمز' : 'Code' }}</label>
                                        <input v-model="form.code" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'الهاتف' : 'Phone' }}</label>
                                        <input v-model="form.phone" dir="ltr" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'البريد' : 'Email' }}</label>
                                        <input v-model="form.email" type="email" dir="ltr" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'جهة التواصل' : 'Contact Person' }}</label>
                                        <input v-model="form.contact_person" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'شروط الدفع' : 'Payment Terms' }}</label>
                                        <input v-model="form.payment_terms" placeholder="Net 30" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'مدة التوصيل' : 'Lead Time' }}</label>
                                        <div class="relative">
                                            <input v-model="form.lead_time_days" type="number" min="0" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-400">{{ isRtl ? 'يوم' : 'days' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'الرقم الضريبي' : 'Tax Number' }}</label>
                                    <input v-model="form.tax_number" dir="ltr" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'العنوان' : 'Address' }}</label>
                                    <textarea v-model="form.address" rows="2" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors resize-none" />
                                </div>
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded text-[#1B365D] focus:ring-[#1B365D]" />
                                    <span class="text-sm font-medium text-gray-700">{{ isRtl ? 'نشط' : 'Active' }}</span>
                                </label>
                                <!-- Actions -->
                                <div class="flex gap-3 pt-4 border-t border-gray-100">
                                    <button type="button" @click="showModal = false" :disabled="processing"
                                        class="flex-1 py-2.5 px-4 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all disabled:opacity-50">
                                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                    </button>
                                    <button type="submit" :disabled="processing"
                                        class="flex-1 py-2.5 px-4 text-sm font-semibold text-white bg-[#1B365D] hover:bg-[#1B365D] rounded-xl transition-all shadow-lg shadow-[#1B365D]/20 disabled:opacity-50 inline-flex items-center justify-center gap-2">
                                        <svg v-if="processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                        {{ editing ? (isRtl ? 'تحديث' : 'Update') : (isRtl ? 'إضافة' : 'Create') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
