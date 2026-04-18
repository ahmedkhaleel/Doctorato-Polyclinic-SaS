<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    insurances: Object,
    companies: Array,
    filters: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

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
    if (confirm(isRtl.value ? 'هل تريد إلغاء تفعيل هذا التأمين؟' : 'Deactivate this insurance?')) {
        router.post(`/admin/patient-insurances/${ins.id}/delete`, {}, { preserveScroll: true })
    }
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
                    <tr v-for="ins in insurances.data" :key="ins.id" class="border-t border-gray-50 hover:bg-gray-50/50" :class="{ 'opacity-60': !ins.is_active }">
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

        <!-- Pagination -->
        <div v-if="insurances.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in insurances.links" :key="link.label" :href="link.url || '#'"
                class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-[#1B365D] text-white' : 'text-gray-500 hover:bg-gray-100'"
                v-html="link.label" preserve-state />
        </div>
    </div>
</template>
