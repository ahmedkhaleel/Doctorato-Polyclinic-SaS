<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ suppliers: Object, filters: Object })

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const showModal = ref(false)
const editing = ref(null)
const form = ref({ name_ar: '', name_en: '', code: '', phone: '', email: '', contact_person: '', address: '', tax_number: '', payment_terms: '', lead_time_days: null, notes: '', is_active: true })

function applyFilters() {
    router.get('/admin/suppliers', { search: search.value || undefined, status: statusFilter.value || undefined }, { preserveState: true, replace: true })
}

function openCreate() { editing.value = null; form.value = { name_ar: '', name_en: '', code: '', phone: '', email: '', contact_person: '', address: '', tax_number: '', payment_terms: '', lead_time_days: null, notes: '', is_active: true }; showModal.value = true }
function openEdit(s) { editing.value = s; form.value = { ...s }; showModal.value = true }

function submit() {
    const url = editing.value ? `/admin/suppliers/${editing.value.id}` : '/admin/suppliers'
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, { onSuccess: () => { showModal.value = false } })
}

function remove(s) {
    if (confirm(isRtl.value ? 'هل أنت متأكد؟' : 'Are you sure?')) router.delete(`/admin/suppliers/${s.id}`)
}
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'الموردين' : 'Suppliers' }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'إدارة الموردين ومعلومات التواصل' : 'Manage suppliers and contact info' }}</p>
            </div>
            <button @click="openCreate" class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-600 text-white rounded-xl hover:bg-cyan-700 transition font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ isRtl ? 'إضافة مورد' : 'Add Supplier' }}
            </button>
        </div>

        <div class="flex flex-wrap gap-3 mb-6">
            <input v-model="search" @keyup.enter="applyFilters" type="text" :placeholder="isRtl ? 'بحث...' : 'Search...'" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm w-64 focus:ring-cyan-500 focus:border-cyan-500" />
            <select v-model="statusFilter" @change="applyFilters" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ isRtl ? 'الكل' : 'All' }}</option>
                <option value="active">{{ isRtl ? 'نشط' : 'Active' }}</option>
                <option value="inactive">{{ isRtl ? 'غير نشط' : 'Inactive' }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'المورد' : 'Supplier' }}</th>
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'التواصل' : 'Contact' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'مدة التوصيل' : 'Lead Time' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'المنتجات' : 'Products' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'شروط الدفع' : 'Payment Terms' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="s in suppliers.data" :key="s.id" class="hover:bg-gray-50/50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ isRtl ? s.name_ar : s.name_en }}</div>
                            <div v-if="s.code" class="text-xs text-gray-400 font-mono">{{ s.code }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <div v-if="s.phone" class="text-xs">{{ s.phone }}</div>
                            <div v-if="s.email" class="text-xs text-gray-400">{{ s.email }}</div>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">
                            {{ s.lead_time_days ? `${s.lead_time_days} ${isRtl ? 'يوم' : 'days'}` : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 bg-cyan-50 text-cyan-700 rounded-full text-xs font-medium">{{ s.supplies_count || 0 }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ s.payment_terms || '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="s.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                {{ s.is_active ? (isRtl ? 'نشط' : 'Active') : (isRtl ? 'معطل' : 'Inactive') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button @click="openEdit(s)" class="p-1.5 text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="remove(s)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="suppliers.data.length === 0" class="text-center py-12 text-gray-400">{{ isRtl ? 'لا يوجد موردين' : 'No suppliers found' }}</div>
        </div>

        <div v-if="suppliers.links && suppliers.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in suppliers.links" :key="link.label" :href="link.url || '#'" class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-cyan-600 text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-5">{{ editing ? (isRtl ? 'تعديل المورد' : 'Edit Supplier') : (isRtl ? 'إضافة مورد' : 'Add Supplier') }}</h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الاسم بالعربي' : 'Name (AR)' }} *</label><input v-model="form.name_ar" required class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الاسم بالإنجليزي' : 'Name (EN)' }} *</label><input v-model="form.name_en" required class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الرمز' : 'Code' }}</label><input v-model="form.code" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm font-mono" /></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الهاتف' : 'Phone' }}</label><input v-model="form.phone" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'البريد' : 'Email' }}</label><input v-model="form.email" type="email" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'جهة التواصل' : 'Contact Person' }}</label><input v-model="form.contact_person" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'شروط الدفع' : 'Payment Terms' }}</label><input v-model="form.payment_terms" placeholder="Net 30" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'مدة التوصيل (أيام)' : 'Lead Time (days)' }}</label><input v-model="form.lead_time_days" type="number" min="0" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                        </div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الرقم الضريبي' : 'Tax Number' }}</label><input v-model="form.tax_number" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'العنوان' : 'Address' }}</label><textarea v-model="form.address" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" /></div>
                        <label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" class="rounded text-cyan-600" /><span class="text-sm text-gray-700">{{ isRtl ? 'نشط' : 'Active' }}</span></label>
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" @click="showModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                            <button type="submit" class="px-5 py-2.5 bg-cyan-600 text-white rounded-xl hover:bg-cyan-700 text-sm font-medium">{{ editing ? (isRtl ? 'تحديث' : 'Update') : (isRtl ? 'إضافة' : 'Create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
