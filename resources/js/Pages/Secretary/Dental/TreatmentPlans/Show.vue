<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    plan: Object,
    treatmentTypes: Array,
});

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
    pending: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    approved: 'bg-blue-50 text-blue-700 border-blue-200',
    in_progress: 'bg-indigo-50 text-indigo-700 border-indigo-200',
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
    normal: 'bg-blue-50 text-blue-700 border-blue-200',
    high: 'bg-orange-50 text-orange-700 border-orange-200',
    urgent: 'bg-red-50 text-red-700 border-red-200',
};

const priorityLabels = computed(() => ({
    low: isRtl.value ? 'منخفض' : 'Low',
    normal: isRtl.value ? 'عادي' : 'Normal',
    high: isRtl.value ? 'مرتفع' : 'High',
    urgent: isRtl.value ? 'عاجل' : 'Urgent',
}));

const treatmentStatusColors = {
    planned: 'bg-gray-50 text-gray-700 border-gray-200',
    in_progress: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

const treatmentStatusLabels = computed(() => ({
    planned: isRtl.value ? 'مخطط' : 'Planned',
    in_progress: isRtl.value ? 'جاري' : 'In Progress',
    completed: isRtl.value ? 'مكتمل' : 'Completed',
    cancelled: isRtl.value ? 'ملغي' : 'Cancelled',
}));

const progressPercent = computed(() => {
    if (!props.plan.estimated_sessions || props.plan.estimated_sessions === 0) return 0;
    return Math.min(Math.round((props.plan.completed_sessions / props.plan.estimated_sessions) * 100), 100);
});

const totalTreatmentsCost = computed(() => {
    if (!props.plan.treatments) return 0;
    return props.plan.treatments.reduce((sum, tr) => sum + (parseFloat(tr.cost) || 0) + (parseFloat(tr.lab_cost) || 0), 0);
});

function getDoctorName(doctor) {
    if (!doctor) return '-';
    return isRtl.value ? (doctor.name_ar || doctor.name_en) : (doctor.name_en || doctor.name_ar);
}

function getTreatmentTypeName(treatment) {
    if (!treatment.treatment_type) return '-';
    return isRtl.value
        ? (treatment.treatment_type.name_ar || treatment.treatment_type.name_en)
        : (treatment.treatment_type.name_en || treatment.treatment_type.name_ar);
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ isRtl ? (plan.title_ar || plan.title_en || `خطة #${plan.id}`) : (plan.title_en || plan.title_ar || `Plan #${plan.id}`) }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'تفاصيل خطة العلاج' : 'Treatment Plan Details' }}</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <Link v-if="plan.patient" :href="`/secretary/dental/chart/${plan.patient.id}`" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-[#C4A265] bg-[#C4A265]/10 rounded-xl hover:bg-[#C4A265]/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    {{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}
                </Link>
                <Link href="/secretary/dental/treatment-plans" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'رجوع' : 'Back' }}
                </Link>
            </div>
        </div>

        <!-- Patient Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ isRtl ? 'معلومات المريض' : 'Patient Information' }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'الاسم' : 'Name' }}</span>
                    <span class="font-semibold text-gray-800">{{ plan.patient?.full_name || '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'رقم الملف' : 'File #' }}</span>
                    <span class="font-mono font-semibold text-gray-800">{{ plan.patient?.file_number || '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'الهاتف' : 'Phone' }}</span>
                    <span class="font-semibold text-gray-800" dir="ltr">{{ plan.patient?.phone || '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Plan Details + Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
            <!-- Plan Details -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 space-y-5">
                <div class="flex items-start justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border" :class="statusColors[plan.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                            {{ statusLabels[plan.status] || plan.status }}
                        </span>
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border" :class="priorityColors[plan.priority] || 'bg-gray-50 text-gray-600 border-gray-200'">
                            {{ priorityLabels[plan.priority] || plan.priority || (isRtl ? 'عادي' : 'Normal') }}
                        </span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div v-if="plan.estimated_sessions">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">{{ isRtl ? 'تقدم الجلسات' : 'Sessions Progress' }}</span>
                        <span class="text-sm text-gray-500">{{ plan.completed_sessions || 0 }}/{{ plan.estimated_sessions }} ({{ progressPercent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full transition-all bg-[#C4A265]" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400 block mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }}</span>
                        <span class="font-medium text-gray-900">{{ getDoctorName(plan.doctor) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block mb-1">{{ isRtl ? 'التكلفة المقدرة' : 'Estimated Cost' }}</span>
                        <span class="font-medium text-gray-900">{{ formatCurrency(plan.estimated_cost) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block mb-1">{{ isRtl ? 'تاريخ البدء' : 'Start Date' }}</span>
                        <span class="font-medium text-gray-900">{{ formatDate(plan.start_date) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block mb-1">{{ isRtl ? 'تاريخ الانتهاء المتوقع' : 'Expected End Date' }}</span>
                        <span class="font-medium text-gray-900">{{ formatDate(plan.expected_end_date) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block mb-1">{{ isRtl ? 'تاريخ الإنشاء' : 'Created' }}</span>
                        <span class="font-medium text-gray-900">{{ formatDate(plan.created_at) }}</span>
                    </div>
                </div>

                <div v-if="plan.description" class="pt-4 border-t border-gray-100">
                    <span class="text-sm text-gray-400 block mb-1">{{ isRtl ? 'الوصف' : 'Description' }}</span>
                    <p class="text-sm text-gray-700">{{ plan.description }}</p>
                </div>

                <div v-if="plan.notes" class="pt-4 border-t border-gray-100">
                    <span class="text-sm text-gray-400 block mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</span>
                    <p class="text-sm text-gray-700">{{ plan.notes }}</p>
                </div>
            </div>

            <!-- Cost Summary -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'ملخص التكاليف' : 'Cost Summary' }}</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">{{ isRtl ? 'التكلفة المقدرة' : 'Estimated Cost' }}</span>
                        <span class="font-semibold text-gray-800">{{ formatCurrency(plan.estimated_cost) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">{{ isRtl ? 'إجمالي تكلفة العلاجات' : 'Treatments Total' }}</span>
                        <span class="font-semibold text-gray-800">{{ formatCurrency(totalTreatmentsCost) }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600">{{ isRtl ? 'عدد العلاجات' : 'Treatments Count' }}</span>
                            <span class="text-2xl font-bold text-[#C4A265]">{{ plan.treatments?.length || 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Treatments Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">{{ isRtl ? 'العلاجات' : 'Treatments' }}</h2>
            </div>

            <div v-if="!plan.treatments || plan.treatments.length === 0" class="py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد علاجات' : 'No treatments found' }}</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'السن' : 'Tooth' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'نوع العلاج' : 'Treatment Type' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'الأسطح' : 'Surfaces' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'تكلفة المختبر' : 'Lab Cost' }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="treatment in plan.treatments" :key="treatment.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3 font-mono font-medium text-gray-900">{{ treatment.tooth_number || '-' }}</td>
                            <td class="px-6 py-3 text-gray-700">{{ getTreatmentTypeName(treatment) }}</td>
                            <td class="px-6 py-3 text-gray-600 hidden sm:table-cell">{{ treatment.surfaces || '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 max-w-[200px] truncate hidden md:table-cell">{{ treatment.description || '-' }}</td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left font-medium text-gray-700">{{ formatCurrency(treatment.cost) }}</td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left text-gray-500 hidden sm:table-cell">{{ formatCurrency(treatment.lab_cost) }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="treatmentStatusColors[treatment.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                    {{ treatmentStatusLabels[treatment.status] || treatment.status || (isRtl ? 'مخطط' : 'Planned') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
