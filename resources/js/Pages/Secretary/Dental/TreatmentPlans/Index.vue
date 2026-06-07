<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import DeskHeader from '@/Components/Secretary/DeskHeader.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    plans: Object,
    filters: Object,
    doctors: Array,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const doctorId = ref(props.filters?.doctor_id || '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch(status, () => applyFilters());
watch(doctorId, () => applyFilters());

function applyFilters() {
    router.get('/secretary/dental/treatment-plans', {
        search: search.value || undefined,
        status: status.value || undefined,
        doctor_id: doctorId.value || undefined,
    }, { preserveState: true, replace: true });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatCurrency(amount) {
    if (!amount && amount !== 0) return '-';
    return parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

const statusColors = {
    draft: 'bg-gray-50 text-gray-700 border-gray-200',
    pending: 'bg-yellow-50 text-amber-700 border-yellow-200',
    approved: 'bg-slate-50 text-[#1B365D] border-slate-200',
    in_progress: 'bg-slate-50 text-[#1B365D] border-slate-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

const statusLabels = computed(() => ({
    draft: isRtl.value ? 'مسودة' : 'Draft',
    pending: isRtl.value ? 'قيد الانتظار' : 'Pending',
    approved: isRtl.value ? 'معتمد' : 'Approved',
    in_progress: isRtl.value ? 'جاري التنفيذ' : 'In Progress',
    completed: isRtl.value ? 'مكتمل' : 'Completed',
    cancelled: isRtl.value ? 'ملغي' : 'Cancelled',
}));

const priorityColors = {
    low: 'bg-gray-50 text-gray-600 border-gray-200',
    normal: 'bg-slate-50 text-[#1B365D] border-slate-200',
    high: 'bg-amber-50 text-amber-700 border-amber-200',
    urgent: 'bg-red-50 text-red-700 border-red-200',
};

const priorityLabels = computed(() => ({
    low: isRtl.value ? 'منخفض' : 'Low',
    normal: isRtl.value ? 'عادي' : 'Normal',
    high: isRtl.value ? 'مرتفع' : 'High',
    urgent: isRtl.value ? 'عاجل' : 'Urgent',
}));

function getDoctorName(plan) {
    if (!plan.doctor) return '-';
    return isRtl.value ? (plan.doctor.name_ar || plan.doctor.name_en) : (plan.doctor.name_en || plan.doctor.name_ar);
}
</script>

<template>
    <div>
        <!-- Header -->
        <DeskHeader accent="#C4A265" class="mb-6"
            :title="isRtl ? 'خطط العلاج' : 'Treatment Plans'"
            :subtitle="isRtl ? 'عرض جميع خطط علاج الأسنان' : 'View all dental treatment plans'">
            <template #actions>
                <Link href="/secretary/dental" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-[#1B365D] bg-white/95 rounded-xl hover:bg-white transition-all">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'رجوع' : 'Back' }}
                </Link>
            </template>
        </DeskHeader>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input
                v-model="search"
                type="text"
                :placeholder="isRtl ? 'بحث بالاسم أو رقم الهاتف...' : 'Search by name or phone...'"
                class="doctorato-input px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] w-full sm:w-72"
            />
            <select v-model="status" class="doctorato-input px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                <option value="draft">{{ isRtl ? 'مسودة' : 'Draft' }}</option>
                <option value="pending">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</option>
                <option value="approved">{{ isRtl ? 'معتمد' : 'Approved' }}</option>
                <option value="in_progress">{{ isRtl ? 'جاري التنفيذ' : 'In Progress' }}</option>
                <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                <option value="cancelled">{{ isRtl ? 'ملغي' : 'Cancelled' }}</option>
            </select>
            <select v-model="doctorId" class="doctorato-input px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'جميع الأطباء' : 'All Doctors' }}</option>
                <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
                    {{ isRtl ? (doc.name_ar || doc.name_en) : (doc.name_en || doc.name_ar) }}
                </option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'العنوان' : 'Title' }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center hidden sm:table-cell">{{ isRtl ? 'الأولوية' : 'Priority' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'التكلفة المقدرة' : 'Est. Cost' }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center hidden md:table-cell">{{ isRtl ? 'العلاجات' : 'Treatments' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="plan in plans.data" :key="plan.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3">
                                <div class="font-semibold text-gray-800">{{ plan.patient?.full_name || '-' }}</div>
                                <div class="text-xs text-gray-400" dir="ltr">{{ plan.patient?.phone || '-' }}</div>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ getDoctorName(plan) }}</td>
                            <td class="px-6 py-3">
                                <Link :href="`/secretary/dental/treatment-plans/${plan.id}`" class="font-medium text-[#C4A265] hover:text-[#b3913a] transition-colors">
                                    {{ isRtl ? (plan.title_ar || plan.title_en || `#${plan.id}`) : (plan.title_en || plan.title_ar || `#${plan.id}`) }}
                                </Link>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="statusColors[plan.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                    {{ statusLabels[plan.status] || plan.status }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center hidden sm:table-cell">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="priorityColors[plan.priority] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                    {{ priorityLabels[plan.priority] || plan.priority || (isRtl ? 'عادي' : 'Normal') }}
                                </span>
                            </td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left font-medium text-gray-700 hidden sm:table-cell">{{ formatCurrency(plan.estimated_cost) }}</td>
                            <td class="px-6 py-3 text-center hidden md:table-cell">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[#C4A265]/10 text-[#C4A265] text-xs font-bold">
                                    {{ plan.treatments_count ?? plan.treatments?.length ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left">
                                <Link :href="`/secretary/dental/treatment-plans/${plan.id}`" class="text-[#C4A265] hover:text-[#b3913a] text-xs font-semibold">
                                    {{ isRtl ? 'عرض' : 'View' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="plans.data?.length === 0" class="py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد خطط علاج' : 'No treatment plans found' }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="plans.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in plans.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
