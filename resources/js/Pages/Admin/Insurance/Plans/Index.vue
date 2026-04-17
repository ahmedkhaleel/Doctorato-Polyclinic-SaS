<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    plans: Object,
    companies: Array,
    filters: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const search = ref(props.filters?.search || '')
const companyFilter = ref(props.filters?.company_id || '')
const classFilter = ref(props.filters?.class || '')

function applyFilters() {
    router.get('/admin/insurance/plans', {
        search: search.value || undefined,
        company_id: companyFilter.value || undefined,
        class: classFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function deletePlan(plan) {
    if (confirm(isRtl.value ? 'هل أنت متأكد من حذف هذه الباقة؟' : 'Delete this plan?')) {
        router.post(`/admin/insurance/plans/${plan.id}/delete`)
    }
}

const classColors = {
    VIP: 'bg-[#C4A265]/15 text-[#8B6F3F] border-[#C4A265]/40',
    A:   'bg-emerald-50 text-emerald-700 border-emerald-200',
    B:   'bg-blue-50 text-blue-700 border-blue-200',
    C:   'bg-indigo-50 text-indigo-700 border-indigo-200',
    D:   'bg-orange-50 text-orange-700 border-orange-200',
    E:   'bg-gray-50 text-gray-700 border-gray-200',
}
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-3xl mb-6 p-7"
             style="background: linear-gradient(135deg, #1B365D 0%, #254677 55%, #1B365D 100%);">
            <div class="absolute inset-0 opacity-20 pointer-events-none"
                 style="background-image: radial-gradient(circle at 20% 50%, #C4A265 0%, transparent 40%);"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl"
                              style="background: rgba(196, 162, 101, 0.2); border: 1px solid rgba(196, 162, 101, 0.4);">
                            <svg class="w-6 h-6 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        </span>
                        {{ isRtl ? 'باقات التأمين' : 'Insurance Plans' }}
                    </h1>
                    <p class="text-sm text-white/70 mt-2">{{ isRtl ? 'إدارة باقات التأمين مع نسب التغطية والاستثناءات' : 'Manage insurance plans with coverage and exclusions' }}</p>
                </div>
                <Link href="/admin/insurance/plans/create"
                      class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition shadow-lg hover:scale-105"
                      style="background: #C4A265; color: #1B365D;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ isRtl ? 'إضافة باقة' : 'Add Plan' }}
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-5 flex flex-wrap gap-3">
            <input v-model="search" @keyup.enter="applyFilters" type="text"
                   :placeholder="isRtl ? 'بحث بالاسم أو الكود...' : 'Search by name or code...'"
                   class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm flex-1 min-w-[200px] focus:ring-[#C4A265] focus:border-[#C4A265]" />
            <select v-model="companyFilter" @change="applyFilters" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'كل الشركات' : 'All Companies' }}</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ isRtl ? c.name_ar : c.name_en }}</option>
            </select>
            <select v-model="classFilter" @change="applyFilters" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'كل الفئات' : 'All Classes' }}</option>
                <option value="VIP">VIP</option>
                <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                <option value="D">D</option><option value="E">E</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead style="background: #1B365D;">
                    <tr class="text-white/90 text-xs uppercase">
                        <th class="px-4 py-3 text-start font-semibold">{{ isRtl ? 'الباقة' : 'Plan' }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ isRtl ? 'شركة التأمين' : 'Company' }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ isRtl ? 'الفئة' : 'Class' }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ isRtl ? 'التغطية' : 'Coverage' }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ isRtl ? 'المشاركة' : 'Copay' }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ isRtl ? 'الحد الأقصى' : 'Max Limit' }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ isRtl ? 'مؤمنون' : 'Insured' }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="plan in plans.data" :key="plan.id" class="border-t border-gray-50 hover:bg-gray-50/50">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ isRtl ? plan.name_ar : plan.name_en }}</div>
                            <div v-if="plan.plan_code" class="text-xs text-gray-400 font-mono">{{ plan.plan_code }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ isRtl ? plan.company?.name_ar : plan.company?.name_en }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold border" :class="classColors[plan.class]">{{ plan.class }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-[#1B365D]">{{ plan.coverage_percentage }}%</span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ plan.copay_amount ? Number(plan.copay_amount).toLocaleString() : '—' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ plan.max_coverage_amount ? Number(plan.max_coverage_amount).toLocaleString() : '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-lg text-xs font-medium bg-[#C4A265]/10 text-[#8B6F3F]">{{ plan.patient_insurances_count }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <Link :href="`/admin/insurance/plans/${plan.id}/edit`" class="p-1.5 text-gray-400 hover:text-[#1B365D] hover:bg-[#C4A265]/10 rounded-lg transition" :title="isRtl ? 'تعديل' : 'Edit'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </Link>
                                <button @click="deletePlan(plan)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" :title="isRtl ? 'حذف' : 'Delete'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="plans.data.length === 0">
                        <td colspan="8" class="text-center py-10 text-gray-400">{{ isRtl ? 'لا توجد باقات' : 'No plans' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="plans.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in plans.links" :key="link.label" :href="link.url || '#'"
                class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-[#1B365D] text-white' : 'text-gray-500 hover:bg-gray-100'"
                v-html="link.label" preserve-state />
        </div>
    </div>
</template>
