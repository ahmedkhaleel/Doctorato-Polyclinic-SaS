<script setup>
import { ref, watch, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    logs: Object,
    stats: Object,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const accessType = ref(props.filters?.access_type || '');
const dataCategory = ref(props.filters?.data_category || '');

function applyFilters() {
    router.get('/admin/medical-access-logs', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        access_type: accessType.value || undefined,
        data_category: dataCategory.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([dateFrom, dateTo, accessType, dataCategory], applyFilters);

const accessTypeLabels = {
    view_medical: { en: 'View', ar: 'عرض', color: 'bg-slate-100 text-[#1B365D]' },
    update_medical: { en: 'Update', ar: 'تعديل', color: 'bg-amber-100 text-amber-700' },
    export_medical: { en: 'Export', ar: 'تصدير', color: 'bg-slate-100 text-[#1B365D]' },
    print_medical: { en: 'Print', ar: 'طباعة', color: 'bg-gray-100 text-gray-700' },
};

const categoryLabels = {
    dental_medical: { en: 'Dental Medical', ar: 'طب الأسنان' },
    risk_flags: { en: 'Risk Flags', ar: 'أعلام الخطورة' },
    sensitive_medical: { en: 'Sensitive', ar: 'بيانات حساسة' },
    full_record: { en: 'Full Record', ar: 'سجل كامل' },
};

function getAccessLabel(type) {
    const l = accessTypeLabels[type];
    return l ? (locale.value === 'ar' ? l.ar : l.en) : type;
}

function getAccessColor(type) {
    return accessTypeLabels[type]?.color || 'bg-gray-100 text-gray-700';
}

function getCategoryLabel(cat) {
    const l = categoryLabels[cat];
    return l ? (locale.value === 'ar' ? l.ar : l.en) : cat;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString(locale.value === 'ar' ? 'ar-EG' : 'en-US', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <AdminLayout :title="locale === 'ar' ? 'سجل الوصول للبيانات الطبية' : 'Medical Data Access Log'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 md:py-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">
                        {{ locale === 'ar' ? 'سجل الوصول للبيانات الطبية' : 'Medical Data Access Log' }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ locale === 'ar' ? 'تتبع من شاهد أو عدّل البيانات الطبية الحساسة للمرضى' : 'Track who viewed or modified sensitive patient medical data' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ stats.total_today }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ locale === 'ar' ? 'عمليات وصول اليوم' : 'Accesses Today' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-red-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-red-600">{{ stats.sensitive_today }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ locale === 'ar' ? 'وصول لبيانات حساسة' : 'Sensitive Accesses' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-gray-700">{{ stats.unique_patients_accessed }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ locale === 'ar' ? 'مرضى تم الوصول لسجلاتهم' : 'Patients Accessed' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-amber-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-amber-600">{{ stats.updates_this_week }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ locale === 'ar' ? 'تعديلات هذا الأسبوع' : 'Updates This Week' }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ locale === 'ar' ? 'من تاريخ' : 'From' }}</label>
                        <input v-model="dateFrom" type="date" class="doctorato-input w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B365D] focus:border-[#1B365D]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ locale === 'ar' ? 'إلى تاريخ' : 'To' }}</label>
                        <input v-model="dateTo" type="date" class="doctorato-input w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B365D] focus:border-[#1B365D]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ locale === 'ar' ? 'نوع الوصول' : 'Access Type' }}</label>
                        <select v-model="accessType" class="doctorato-input w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B365D] focus:border-[#1B365D]">
                            <option value="">{{ locale === 'ar' ? 'الكل' : 'All' }}</option>
                            <option value="view_medical">{{ locale === 'ar' ? 'عرض' : 'View' }}</option>
                            <option value="update_medical">{{ locale === 'ar' ? 'تعديل' : 'Update' }}</option>
                            <option value="export_medical">{{ locale === 'ar' ? 'تصدير' : 'Export' }}</option>
                            <option value="print_medical">{{ locale === 'ar' ? 'طباعة' : 'Print' }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ locale === 'ar' ? 'التصنيف' : 'Category' }}</label>
                        <select v-model="dataCategory" class="doctorato-input w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B365D] focus:border-[#1B365D]">
                            <option value="">{{ locale === 'ar' ? 'الكل' : 'All' }}</option>
                            <option value="dental_medical">{{ locale === 'ar' ? 'طب أسنان' : 'Dental Medical' }}</option>
                            <option value="risk_flags">{{ locale === 'ar' ? 'أعلام الخطورة' : 'Risk Flags' }}</option>
                            <option value="sensitive_medical">{{ locale === 'ar' ? 'بيانات حساسة' : 'Sensitive' }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">{{ locale === 'ar' ? 'التاريخ' : 'Date' }}</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">{{ locale === 'ar' ? 'المستخدم' : 'User' }}</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">{{ locale === 'ar' ? 'المريض' : 'Patient' }}</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">{{ locale === 'ar' ? 'النوع' : 'Type' }}</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">{{ locale === 'ar' ? 'التصنيف' : 'Category' }}</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">{{ locale === 'ar' ? 'اللوحة' : 'Panel' }}</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(log, i) in logs.data" :key="log.id" class="lst-row hover:bg-gray-50/50" :style="{ '--row-i': i }">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ formatDate(log.created_at) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ log.user?.name || '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <Link v-if="log.patient" :href="`/admin/patients/${log.patient_id}`" class="text-[#1B365D] hover:underline">
                                        {{ log.patient.full_name }}
                                        <span class="text-gray-400 text-xs ms-1">{{ log.patient.file_number }}</span>
                                    </Link>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', getAccessColor(log.access_type)]">
                                        {{ getAccessLabel(log.access_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    <span v-if="log.data_category === 'sensitive_medical'" class="text-red-600 font-medium">
                                        {{ getCategoryLabel(log.data_category) }}
                                    </span>
                                    <span v-else>{{ getCategoryLabel(log.data_category) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500 capitalize">{{ log.panel || '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-400 text-xs font-mono">{{ log.ip_address }}</td>
                            </tr>
                            <tr v-if="!logs.data?.length">
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                    {{ locale === 'ar' ? 'لا توجد سجلات وصول' : 'No access logs found' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200">
                    <div class="text-xs text-gray-500">
                        {{ locale === 'ar' ? `${logs.total} سجل` : `${logs.total} records` }}
                    </div>
                    <div class="flex gap-1">
                        <Link v-for="link in logs.links" :key="link.label"
                            :href="link.url || '#'"
                            :class="['px-3 py-1 text-xs rounded-lg border', link.active ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']"
                            v-html="link.label"
                            preserve-state
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
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
