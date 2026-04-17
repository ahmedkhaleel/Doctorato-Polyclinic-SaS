<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visit: Object,
    dentalChart: Object,
    dentalConditions: Object,
    allTeeth: Object,
    treatmentTypes: Object,
});

const isDental = computed(() => props.visit?.module === 'dental');

/* ── Status helpers ─────────────────────────────────────── */
const statusLabels = {
    waiting: 'Waiting',
    in_progress: 'In Progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const statusColors = {
    waiting: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    in_progress: 'bg-slate-100 text-[#1B365D] border-slate-200',
    completed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    cancelled: 'bg-red-100 text-red-800 border-red-200',
};

const statusDotColors = {
    waiting: 'bg-amber-500',
    in_progress: 'bg-[#1B365D]',
    completed: 'bg-emerald-500',
    cancelled: 'bg-red-500',
};

const visitTypeLabels = {
    consultation: isRtl.value ? 'استشارة' : 'Consultation',
    session: isRtl.value ? 'جلسة' : 'Session',
    follow_up: isRtl.value ? 'متابعة' : 'Follow Up',
};

const visitTypeBadgeColors = {
    consultation: 'bg-teal-100 text-teal-700',
    session: 'bg-slate-100 text-[#1B365D]',
    follow_up: 'bg-slate-100 text-[#1B365D]',
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatDateTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/* ── Actions ────────────────────────────────────────────── */
function cancelVisit() {
    const msg = isRtl.value ? 'هل أنت متأكد من إلغاء هذه الزيارة؟' : 'Are you sure you want to cancel this visit?';
    if (window.confirm(msg)) {
        router.post(`/secretary/visits/${props.visit.id}/cancel`, {}, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <Link href="/secretary/visits" class="text-teal-600 hover:text-teal-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'تفاصيل الزيارة' : 'Visit Details' }}</h1>
                    <span
                        :class="statusColors[visit.status]"
                        class="px-3 py-1 text-xs font-semibold rounded-full border flex items-center gap-1.5"
                    >
                        <span :class="statusDotColors[visit.status]" class="w-1.5 h-1.5 rounded-full"></span>
                        {{ statusLabels[visit.status] || visit.status }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 ml-8">{{ isRtl ? 'زيارة' : 'Visit' }} #{{ visit.id }} &middot; {{ formatDate(visit.visit_date) }}</p>
            </div>

            <!-- Action Buttons (Secretary can only cancel) -->
            <div class="flex items-center gap-2 ml-8 sm:ml-0">
                <button
                    v-if="visit.status === 'waiting' || visit.status === 'in_progress'"
                    @click="cancelVisit"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 text-sm font-medium transition-all duration-200 border border-red-200"
                >
                    <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ isRtl ? 'إلغاء الزيارة' : 'Cancel Visit' }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Visit Information Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-teal-50 to-slate-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-teal-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Visit Information
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'المريض' : 'Patient' }}</dt>
                                <dd class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-teal-500 to-[#1B365D] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                        {{ visit.patient?.full_name?.charAt(0) || '?' }}
                                    </div>
                                    {{ visit.patient?.full_name || '-' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }}</dt>
                                <dd class="text-sm text-gray-900">{{ visit.doctor?.name_en || visit.doctor?.name || '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الخدمة' : 'Service' }}</dt>
                                <dd class="text-sm text-gray-900">{{ visit.service?.name_en || visit.service?.name || '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'نوع الزيارة' : 'Visit Type' }}</dt>
                                <dd>
                                    <span
                                        :class="visitTypeBadgeColors[visit.visit_type] || 'bg-gray-100 text-gray-600'"
                                        class="px-2.5 py-0.5 text-xs font-semibold rounded-full"
                                    >
                                        {{ visitTypeLabels[visit.visit_type] || visit.visit_type }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'تاريخ الزيارة' : 'Visit Date' }}</dt>
                                <dd class="text-sm text-gray-900">{{ formatDate(visit.visit_date) }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الحالة' : 'Status' }}</dt>
                                <dd>
                                    <span
                                        :class="statusColors[visit.status]"
                                        class="px-2.5 py-0.5 text-xs font-semibold rounded-full border inline-flex items-center gap-1.5"
                                    >
                                        <span :class="statusDotColors[visit.status]" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ statusLabels[visit.status] || visit.status }}
                                    </span>
                                </dd>
                            </div>

                            <div v-if="visit.session_number">
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'رقم الجلسة' : 'Session Number' }}</dt>
                                <dd class="text-sm text-gray-900">Session #{{ visit.session_number }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'تاريخ الإنشاء' : 'Created At' }}</dt>
                                <dd class="text-sm text-gray-500">{{ formatDateTime(visit.created_at) }}</dd>
                            </div>

                            <div v-if="visit.updated_at !== visit.created_at">
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'آخر تحديث' : 'Last Updated' }}</dt>
                                <dd class="text-sm text-gray-500">{{ formatDateTime(visit.updated_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Prescriptions Section -->
                <div v-if="visit.prescriptions && visit.prescriptions.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-teal-50 to-slate-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-teal-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Prescriptions
                            <span class="ltr:ml-auto rtl:mr-auto text-xs font-normal text-teal-600 bg-teal-100 px-2 py-0.5 rounded-full">
                                {{ visit.prescriptions.length }}
                            </span>
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="prescription in visit.prescriptions" :key="prescription.id" class="p-3 sm:p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900 mb-1">{{ isRtl ? 'وصفة' : 'Prescription' }} #{{ prescription.id }}</h3>
                                    <p v-if="prescription.notes" class="text-sm text-gray-600">{{ prescription.notes }}</p>
                                    <div v-if="prescription.items && prescription.items.length" class="mt-3 space-y-1.5">
                                        <div v-for="item in prescription.items" :key="item.id" class="flex items-center gap-2 text-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-teal-400 flex-shrink-0"></span>
                                            <span class="text-gray-700">{{ item.medication?.name || item.medication_name }}</span>
                                            <span v-if="item.dosage" class="text-gray-400">&mdash; {{ item.dosage }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-400">{{ formatDate(prescription.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dental Section -->
                <div v-if="isDental" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-teal-50 to-slate-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-teal-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                            {{ isRtl ? 'طب الأسنان' : 'Dental Information' }}
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6 space-y-6">
                        <!-- Dental Treatments -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                {{ isRtl ? 'علاجات الأسنان' : 'Treatments' }}
                                <span v-if="visit.dental_treatments?.length" class="text-xs font-medium px-2 py-0.5 rounded-full bg-teal-50 text-teal-600 border border-teal-100">{{ visit.dental_treatments.length }}</span>
                            </h3>
                            <div v-if="visit.dental_treatments?.length" class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                                            <th class="text-left py-2 pr-4">{{ isRtl ? 'السن' : 'Tooth' }}</th>
                                            <th class="text-left py-2 pr-4">{{ isRtl ? 'النوع' : 'Type' }}</th>
                                            <th class="text-left py-2 pr-4">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                                            <th class="text-left py-2 pr-4">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                            <th class="text-right py-2">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="t in visit.dental_treatments" :key="t.id" class="hover:bg-gray-50/50">
                                            <td class="py-2 pr-4 font-mono text-teal-600 text-xs">#{{ t.tooth_number || '-' }}</td>
                                            <td class="py-2 pr-4"><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-teal-50 text-teal-700">{{ treatmentTypes?.[t.treatment_type] || t.treatment_type }}</span></td>
                                            <td class="py-2 pr-4 text-gray-600 text-xs">{{ t.description || '-' }}</td>
                                            <td class="py-2 pr-4">
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize" :class="{
                                                    'bg-emerald-100 text-emerald-700': t.status === 'completed',
                                                    'bg-slate-100 text-[#1B365D]': t.status === 'in_progress',
                                                    'bg-yellow-100 text-amber-700': t.status === 'planned',
                                                    'bg-red-100 text-red-700': t.status === 'cancelled',
                                                }">{{ t.status }}</span>
                                            </td>
                                            <td class="py-2 text-right text-xs font-medium text-gray-700">{{ t.cost ? Number(t.cost).toFixed(2) : '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد علاجات أسنان' : 'No dental treatments for this visit' }}</p>
                        </div>

                        <!-- Mini Dental Chart -->
                        <div v-if="dentalChart && Object.keys(dentalChart).length > 0">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h3>
                            <div class="flex flex-wrap gap-1 mb-2">
                                <template v-for="quadrant in ['upper_right', 'upper_left', 'lower_left', 'lower_right']" :key="quadrant">
                                    <div v-for="tooth in (allTeeth?.[quadrant] || [])" :key="tooth"
                                        class="w-7 h-7 rounded flex items-center justify-center text-[10px] font-mono border"
                                        :class="dentalChart[tooth] ? 'bg-amber-50 border-amber-300 text-amber-700 font-bold' : 'bg-gray-50 border-gray-200 text-gray-400'"
                                        :title="dentalChart[tooth]?.condition || 'Healthy'"
                                    >{{ tooth }}</div>
                                </template>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-500 mt-2">
                                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded border border-gray-200 bg-gray-50"></span> {{ isRtl ? 'سليم' : 'Healthy' }}</span>
                                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded border border-amber-300 bg-amber-50"></span> {{ isRtl ? 'يحتاج علاج' : 'Has Condition' }}</span>
                            </div>
                            <Link :href="`/secretary/dental/patient-chart/${visit.patient_id}`" class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 hover:text-teal-700 mt-3 transition">
                                {{ isRtl ? 'عرض المخطط الكامل' : 'View Full Chart' }}
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>

                        <!-- Quick Links -->
                        <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                            <Link :href="`/secretary/dental/patient-chart/${visit.patient_id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-lg border border-teal-200 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" /></svg>
                                {{ isRtl ? 'مخطط المريض' : 'Patient Chart' }}
                            </Link>
                            <Link href="/secretary/dental/treatment-plans" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                {{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}
                            </Link>
                            <Link href="/secretary/dental/lab-orders" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                {{ isRtl ? 'طلبات المعمل' : 'Lab Orders' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Patient Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-teal-50 to-slate-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-teal-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Patient Info
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-500 to-[#1B365D] flex items-center justify-center text-white text-lg font-bold">
                                {{ visit.patient?.full_name?.charAt(0) || '?' }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ visit.patient?.full_name || '-' }}</p>
                                <p v-if="visit.patient?.file_number" class="text-xs text-teal-600 font-mono">{{ isRtl ? 'ملف' : 'File' }} #{{ visit.patient.file_number }}</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div v-if="visit.patient?.phone" class="flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span class="text-gray-600">{{ visit.patient.phone }}</span>
                            </div>
                            <div v-if="visit.patient?.email" class="flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="text-gray-600">{{ visit.patient.email }}</span>
                            </div>
                            <div v-if="visit.patient?.gender" class="flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="text-gray-600 capitalize">{{ visit.patient.gender }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div v-if="visit.invoice" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-teal-50 to-slate-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-teal-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                            </svg>
                            Invoice
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'فاتورة #' : 'Invoice #' }}</span>
                            <span class="font-mono font-medium text-gray-900">{{ visit.invoice.invoice_number || visit.invoice.id }}</span>
                        </div>
                        <div v-if="visit.invoice.subtotal != null" class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'المجموع الفرعي' : 'Subtotal' }}</span>
                            <span class="text-gray-700">{{ Number(visit.invoice.subtotal).toFixed(2) }}</span>
                        </div>
                        <div v-if="visit.invoice.discount_amount != null && visit.invoice.discount_amount > 0" class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'الخصم' : 'Discount' }}</span>
                            <span class="text-emerald-600">-{{ Number(visit.invoice.discount_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-gray-100 pt-2">
                            <span class="text-gray-700 font-medium">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                            <span class="font-bold text-teal-700 text-base">{{ Number(visit.invoice.total || visit.invoice.total_amount || 0).toFixed(2) }}</span>
                        </div>
                        <div v-if="visit.invoice.status" class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'الحالة' : 'Status' }}</span>
                            <span
                                :class="{
                                    'bg-emerald-100 text-emerald-700': visit.invoice.status === 'paid',
                                    'bg-yellow-100 text-amber-700': visit.invoice.status === 'pending' || visit.invoice.status === 'partial',
                                    'bg-red-100 text-red-700': visit.invoice.status === 'cancelled',
                                }"
                                class="px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                            >
                                {{ visit.invoice.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Linked Booking -->
                <div v-if="visit.booking" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'الحجز المرتبط' : 'Linked Booking' }}</h3>
                    <Link :href="`/secretary/bookings/${visit.booking.id}`" class="block p-3 rounded-lg border border-teal-200 bg-teal-50/50 hover:border-teal-300 transition group">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-teal-600">{{ visit.booking.booking_number || `#${visit.booking.id}` }}</p>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold capitalize" :class="{
                                'bg-yellow-100 text-amber-700': visit.booking.status === 'unconfirmed',
                                'bg-slate-100 text-[#1B365D]': visit.booking.status === 'confirmed',
                                'bg-slate-100 text-[#1B365D]': visit.booking.status === 'in_progress',
                                'bg-emerald-100 text-emerald-700': visit.booking.status === 'completed',
                                'bg-red-100 text-red-700': visit.booking.status === 'cancelled',
                            }">{{ visit.booking.status?.replace('_', ' ') }}</span>
                        </div>
                        <p v-if="visit.booking.booking_type" class="text-xs text-gray-500 mt-1 capitalize">{{ visit.booking.booking_type?.replace(/_/g, ' ') }}</p>
                        <p v-if="visit.booking_appointment" class="text-xs text-gray-400 mt-1">
                            Appointment: {{ formatDate(visit.booking_appointment?.appointment_date) }}
                            <span v-if="visit.booking_appointment?.start_time"> · {{ visit.booking_appointment.start_time?.substring(0, 5) }}</span>
                        </p>
                    </Link>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'إجراءات سريعة' : 'Quick Actions' }}</h3>
                    <div class="space-y-2">
                        <Link
                            href="/secretary/visits"
                            class="w-full flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition border border-gray-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            All Visits
                        </Link>
                        <Link
                            href="/secretary/queue"
                            class="w-full flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-teal-700 hover:bg-teal-50 transition border border-teal-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Today's Queue
                        </Link>
                        <Link
                            href="/secretary/bookings/create"
                            class="w-full flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition shadow-sm"
                            style="background: linear-gradient(135deg, #0d9488, #06b6d4);"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ isRtl ? 'حجز جديد' : 'New Booking' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
