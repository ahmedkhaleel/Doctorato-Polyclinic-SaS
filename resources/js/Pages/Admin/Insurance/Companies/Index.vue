<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { useConfirm } from '@/Composables/useConfirm.js'

defineOptions({ layout: AdminLayout })

const { confirm } = useConfirm()

const props = defineProps({
    companies: Object,
    filters: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const showCreateModal = ref(false)
const editingCompany = ref(null)
const form = ref({
    name_ar: '', name_en: '', code: '', phone: '', email: '',
    contact_person: '', address: '', notes: '', is_active: true,
})

function applyFilters() {
    router.get('/admin/insurance/companies', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetForm() {
    form.value = { name_ar: '', name_en: '', code: '', phone: '', email: '', contact_person: '', address: '', notes: '', is_active: true }
    editingCompany.value = null
}

function openCreate() {
    resetForm()
    showCreateModal.value = true
}

function openEdit(company) {
    editingCompany.value = company
    form.value = { ...company }
    showCreateModal.value = true
}

function submit() {
    if (editingCompany.value) {
        router.post(`/admin/insurance/companies/${editingCompany.value.id}/update`, form.value, {
            onSuccess: () => { showCreateModal.value = false; resetForm() }
        })
    } else {
        router.post('/admin/insurance/companies', form.value, {
            onSuccess: () => { showCreateModal.value = false; resetForm() }
        })
    }
}

function deleteCompany(company) {
    confirm(isRtl.value ? 'هل أنت متأكد من حذف هذه الشركة؟' : 'Are you sure you want to delete this company?', () => {
        router.post(`/admin/insurance/companies/${company.id}/delete`)
    })
}
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'شركات التأمين' : 'Insurance Companies' }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'إدارة شركات التأمين والخطط التأمينية' : 'Manage insurance companies and plans' }}</p>
            </div>
            <button @click="openCreate" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#142849] transition font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ isRtl ? 'إضافة شركة' : 'Add Company' }}
            </button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <input v-model="search" @keyup.enter="applyFilters" type="text" :placeholder="isRtl ? 'بحث...' : 'Search...'" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm w-64 focus:ring-[#C4A265] focus:border-[#C4A265]" />
            <select v-model="statusFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'الكل' : 'All' }}</option>
                <option value="active">{{ isRtl ? 'نشط' : 'Active' }}</option>
                <option value="inactive">{{ isRtl ? 'غير نشط' : 'Inactive' }}</option>
            </select>
        </div>

        <!-- Companies Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div v-for="company in companies.data" :key="company.id" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-lg transition-all group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div v-if="company.logo_url" class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100">
                            <img :src="company.logo_url" :alt="company.name_en" class="w-full h-full object-contain" />
                        </div>
                        <div v-else class="w-12 h-12 rounded-xl bg-[#1B365D]/5 flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">{{ isRtl ? company.name_ar : company.name_en }}</h3>
                            <p v-if="company.code" class="text-xs text-gray-400 font-mono">{{ company.code }}</p>
                        </div>
                    </div>
                    <span :class="company.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ company.is_active ? (isRtl ? 'نشط' : 'Active') : (isRtl ? 'معطل' : 'Inactive') }}
                    </span>
                </div>

                <div class="space-y-2 text-sm text-gray-500">
                    <div v-if="company.phone" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ company.phone }}
                    </div>
                    <div v-if="company.email" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ company.email }}
                    </div>
                </div>

                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-50">
                    <div class="flex items-center gap-4 text-xs text-gray-400">
                        <span>{{ company.plans_count || 0 }} {{ isRtl ? 'خطط' : 'plans' }}</span>
                        <span>{{ company.patient_insurances_count || 0 }} {{ isRtl ? 'مؤمنين' : 'insured' }}</span>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                        <button @click="openEdit(company)" class="p-1.5 text-gray-400 hover:text-[#1B365D] hover:bg-[#1B365D]/5 rounded-lg transition" :aria-label="isRtl ? 'تعديل' : 'Edit'" :title="isRtl ? 'تعديل' : 'Edit'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button @click="deleteCompany(company)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" :aria-label="isRtl ? 'حذف' : 'Delete'" :title="isRtl ? 'حذف' : 'Delete'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="companies.data.length === 0" class="text-center py-16">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <h3 class="text-gray-500 font-medium">{{ isRtl ? 'لا توجد شركات تأمين' : 'No insurance companies' }}</h3>
            <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'ابدأ بإضافة شركة تأمين جديدة' : 'Start by adding a new insurance company' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="companies.links && companies.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in companies.links" :key="link.label" :href="link.url || '#'"
                class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-[#1B365D] text-white' : 'text-gray-500 hover:bg-gray-100'"
                v-html="link.label" preserve-state />
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showCreateModal = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-5">
                        {{ editingCompany ? (isRtl ? 'تعديل شركة التأمين' : 'Edit Insurance Company') : (isRtl ? 'إضافة شركة تأمين' : 'Add Insurance Company') }}
                    </h2>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الاسم بالعربي' : 'Name (Arabic)' }} *</label>
                                <input v-model="form.name_ar" type="text" required class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الاسم بالإنجليزي' : 'Name (English)' }} *</label>
                                <input v-model="form.name_en" type="text" required class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الرمز' : 'Code' }}</label>
                                <input v-model="form.code" type="text" :placeholder="isRtl ? 'مثال: BUPA' : 'e.g., BUPA'" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265] font-mono" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الهاتف' : 'Phone' }}</label>
                                <input v-model="form.phone" type="tel" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'البريد' : 'Email' }}</label>
                                <input v-model="form.email" type="email" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'جهة التواصل' : 'Contact Person' }}</label>
                                <input v-model="form.contact_person" type="text" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'العنوان' : 'Address' }}</label>
                            <textarea v-model="form.address" rows="2" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="form.notes" rows="2" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="rounded text-[#1B365D] focus:ring-[#C4A265]" />
                            <span class="text-sm text-gray-700">{{ isRtl ? 'نشط' : 'Active' }}</span>
                        </label>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl transition text-sm font-medium">
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#142849] transition text-sm font-medium">
                                {{ editingCompany ? (isRtl ? 'تحديث' : 'Update') : (isRtl ? 'إضافة' : 'Create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
