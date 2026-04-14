<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import ConfirmModal from '@/Components/Doctor/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const { t } = useLocale();
const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    plan: Object,
    treatmentTypes: Array,
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-[#C4A265]/10 text-[#C4A265]',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const treatmentStatusColors = {
    planned: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-[#C4A265]/10 text-[#C4A265]',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};

const priorityColors = {
    low: 'bg-gray-100 text-gray-600',
    normal: 'bg-blue-100 text-blue-700',
    high: 'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
};

const statusLabels = computed(() => ({
    draft: isRtl.value ? 'مسودة' : 'Draft',
    pending: isRtl.value ? 'قيد الانتظار' : 'Pending',
    approved: isRtl.value ? 'معتمد' : 'Approved',
    in_progress: isRtl.value ? 'قيد التنفيذ' : 'In Progress',
    completed: isRtl.value ? 'مكتمل' : 'Completed',
    cancelled: isRtl.value ? 'ملغي' : 'Cancelled',
}));

const treatmentStatusLabels = computed(() => ({
    planned: isRtl.value ? 'مخطط' : 'Planned',
    in_progress: isRtl.value ? 'قيد التنفيذ' : 'In Progress',
    completed: isRtl.value ? 'مكتمل' : 'Completed',
    cancelled: isRtl.value ? 'ملغي' : 'Cancelled',
}));

const priorityLabels = computed(() => ({
    low: isRtl.value ? 'منخفض' : 'Low',
    normal: isRtl.value ? 'عادي' : 'Normal',
    high: isRtl.value ? 'مرتفع' : 'High',
    urgent: isRtl.value ? 'عاجل' : 'Urgent',
}));

const progressPercent = computed(() => {
    if (!props.plan.estimated_sessions || props.plan.estimated_sessions === 0) return 0;
    return Math.min(Math.round((props.plan.completed_sessions / props.plan.estimated_sessions) * 100), 100);
});

const totalTreatmentsCost = computed(() => {
    if (!props.plan.treatments) return 0;
    return props.plan.treatments.reduce((sum, tr) => sum + (parseFloat(tr.cost) || 0) + (parseFloat(tr.lab_cost) || 0), 0);
});

const updatingStatus = ref(false);
const statusForm = useForm({
    status: '',
});

// ─── Toast Notification ─────────────────────────────────
const showSuccess = ref(false);
const successMessage = ref('');
const showErrorToast = ref(false);
const errorToastMsg = ref('');
const page_flash = usePage();
watch(() => page_flash.props.flash?.success, (msg) => {
    if (msg) { successMessage.value = msg; showSuccess.value = true; setTimeout(() => { showSuccess.value = false; }, 4000); }
});
watch(() => page_flash.props.flash?.error, (msg) => {
    if (msg) { errorToastMsg.value = msg; showErrorToast.value = true; setTimeout(() => { showErrorToast.value = false; }, 5000); }
});

// ─── Confirm Modals ─────────────────────────────────────
const showStatusModal = ref(false);
const pendingStatus = ref(null);

function confirmStatusChange(newStatus) {
    pendingStatus.value = newStatus;
    showStatusModal.value = true;
}

function executeStatusChange() {
    if (!pendingStatus.value) return;
    updatingStatus.value = true;
    showStatusModal.value = false;
    statusForm.status = pendingStatus.value;
    statusForm.post(`/doctor/dental/treatment-plans/${props.plan.id}/update`, {
        preserveScroll: true,
        onFinish: () => {
            updatingStatus.value = false;
            pendingStatus.value = null;
        },
    });
}

// ─── Consent ─────────────────────────────────────────────
const showConsentModal = ref(false);
const consentRisksNotes = ref('');
const consentExpiryDays = ref(7);
const sendingConsent = ref(false);

const latestConsent = computed(() => props.plan?.consent);
const consentHistory = computed(() => props.plan?.consents || []);
const hasSignedConsent = computed(() => latestConsent.value?.status === 'signed');
const hasPendingConsent = computed(() => latestConsent.value?.status === 'pending');
const needsConsent = computed(() => props.plan.status === 'approved' && !hasSignedConsent.value);

const consentStatusColors = {
    pending: 'bg-yellow-100 text-yellow-700',
    signed: 'bg-green-100 text-green-700',
    declined: 'bg-red-100 text-red-700',
    expired: 'bg-gray-100 text-gray-600',
};

function consentStatusLabel(status) {
    const labels = {
        pending: isRtl.value ? 'في انتظار التوقيع' : 'Pending Signature',
        signed: isRtl.value ? 'تم التوقيع' : 'Signed',
        declined: isRtl.value ? 'مرفوض' : 'Declined',
        expired: isRtl.value ? 'منتهي' : 'Expired',
    };
    return labels[status] || status;
}

function sendConsent() {
    sendingConsent.value = true;
    router.post(`/doctor/dental/treatment-plans/${props.plan.id}/consent/send`, {
        risks_notes: consentRisksNotes.value || null,
        expires_in_days: consentExpiryDays.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            sendingConsent.value = false;
            showConsentModal.value = false;
            consentRisksNotes.value = '';
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-7">
            <div class="absolute -top-12 ltr:-right-12 rtl:-left-12 w-48 h-48 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-8 ltr:left-20 rtl:right-20 w-32 h-32 bg-purple-500/10 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link href="/doctor/dental/treatment-plans" class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center hover:bg-white/20 transition ring-1 ring-white/15">
                        <svg class="w-5 h-5 text-white rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <p class="text-[#C4A265]/80 text-xs font-semibold tracking-wider uppercase">{{ isRtl ? 'خطة العلاج' : 'Treatment Plan' }} #{{ plan.id }}</p>
                        <h1 class="text-xl sm:text-2xl font-bold text-white mt-0.5">
                            {{ locale === 'ar' ? (plan.title_ar || plan.title_en || `#${plan.id}`) : (plan.title_en || plan.title_ar || `#${plan.id}`) }}
                        </h1>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <Link v-if="plan.patient" :href="`/doctor/dental/chart/${plan.patient.id}`"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white/90 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 border border-white/15 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        {{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}
                    </Link>
                    <a :href="`/doctor/dental/treatment-plans/${plan.id}/pdf`" target="_blank"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white/90 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 border border-white/15 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Plan Info + Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="dental-card-enter md:col-span-2 lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 space-y-5" style="animation-delay:0.1s">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span :class="[statusColors[plan.status] || 'bg-gray-100 text-gray-800', 'px-3 py-1 rounded-full text-sm font-medium']">
                            {{ statusLabels[plan.status] || plan.status }}
                        </span>
                        <span :class="[priorityColors[plan.priority] || 'bg-gray-100 text-gray-600', 'px-3 py-1 rounded-full text-sm font-medium']">
                            {{ priorityLabels[plan.priority] || priorityLabels['normal'] }}
                        </span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">{{ isRtl ? 'تقدم الجلسات' : 'Sessions Progress' }}</span>
                        <span class="text-sm text-gray-500">{{ plan.completed_sessions }}/{{ plan.estimated_sessions }} ({{ progressPercent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div
                            class="h-3 rounded-full transition-all bg-[#C4A265]"
                            :style="{ width: progressPercent + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 block">{{ isRtl ? 'المريض' : 'Patient' }}</span>
                        <span class="font-medium text-gray-900">{{ plan.patient?.full_name || '-' }}</span>
                        <div v-if="plan.patient?.file_number" class="text-xs text-gray-400 font-mono">{{ plan.patient.file_number }}</div>
                    </div>
                    <div>
                        <span class="text-gray-500 block">{{ isRtl ? 'التكلفة المتوقعة' : 'Estimated Cost' }}</span>
                        <span class="font-medium text-gray-900">{{ formatCurrency(plan.estimated_cost) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">{{ isRtl ? 'تاريخ البدء' : 'Start Date' }}</span>
                        <span class="font-medium text-gray-900">{{ formatDate(plan.start_date) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">{{ isRtl ? 'تاريخ الانتهاء المتوقع' : 'Expected End Date' }}</span>
                        <span class="font-medium text-gray-900">{{ formatDate(plan.expected_end_date) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">{{ isRtl ? 'تاريخ الإنشاء' : 'Created At' }}</span>
                        <span class="font-medium text-gray-900">{{ formatDate(plan.created_at) }}</span>
                    </div>
                </div>

                <div v-if="plan.description" class="pt-3 border-t">
                    <span class="text-sm text-gray-500 block mb-1">{{ isRtl ? 'الوصف' : 'Description' }}</span>
                    <p class="text-sm text-gray-700">{{ plan.description }}</p>
                </div>

                <div v-if="plan.notes" class="pt-3 border-t">
                    <span class="text-sm text-gray-500 block mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</span>
                    <p class="text-sm text-gray-700">{{ plan.notes }}</p>
                </div>
            </div>

            <!-- Status Actions Sidebar -->
            <div class="dental-card-enter md:col-span-2 lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 space-y-4" style="animation-delay:0.15s">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'إجراءات' : 'Actions' }}</h3>

                <div class="space-y-2">
                    <button
                        v-if="plan.status === 'draft' || plan.status === 'pending'"
                        @click="confirmStatusChange('approved')"
                        :disabled="updatingStatus"
                        class="w-full px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ isRtl ? 'اعتماد الخطة' : 'Approve Plan' }}
                    </button>
                    <button
                        v-if="plan.status === 'approved' && hasSignedConsent"
                        @click="confirmStatusChange('in_progress')"
                        :disabled="updatingStatus"
                        class="w-full px-4 py-2.5 text-sm font-medium text-white bg-[#C4A265] rounded-xl hover:bg-[#B39255] disabled:opacity-50 transition"
                    >
                        {{ isRtl ? 'بدء الخطة' : 'Start Plan' }}
                    </button>
                    <div v-else-if="plan.status === 'approved' && !hasSignedConsent" class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                        <p class="text-xs text-amber-700 font-medium flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            {{ isRtl ? 'يجب توقيع المريض قبل بدء العلاج' : 'Patient must sign consent first' }}
                        </p>
                    </div>
                    <button
                        v-if="plan.status === 'in_progress'"
                        @click="confirmStatusChange('completed')"
                        :disabled="updatingStatus"
                        class="w-full px-4 py-2.5 text-sm font-medium text-white bg-green-600 rounded-xl hover:bg-green-700 disabled:opacity-50 transition"
                    >
                        {{ isRtl ? 'إكمال الخطة' : 'Complete Plan' }}
                    </button>
                    <button
                        v-if="plan.status !== 'completed' && plan.status !== 'cancelled'"
                        @click="confirmStatusChange('cancelled')"
                        :disabled="updatingStatus"
                        class="w-full px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 rounded-xl hover:bg-red-100 disabled:opacity-50 transition"
                    >
                        {{ isRtl ? 'إلغاء الخطة' : 'Cancel Plan' }}
                    </button>
                </div>

                <div class="pt-4 border-t">
                    <div class="text-sm text-gray-500 mb-1">{{ isRtl ? 'إجمالي تكلفة العلاجات' : 'Total Treatments Cost' }}</div>
                    <div class="text-2xl font-bold text-[#C4A265]">{{ formatCurrency(totalTreatmentsCost) }}</div>
                </div>
            </div>
        </div>

        <!-- Consent Section -->
        <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay:0.2s">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                         :class="hasSignedConsent ? 'bg-green-100' : hasPendingConsent ? 'bg-yellow-100' : 'bg-gray-100'">
                        <svg v-if="hasSignedConsent" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'موافقة المريض' : 'Patient Consent' }}</h2>
                        <p class="text-xs text-gray-400">{{ isRtl ? 'التوقيع الرقمي على خطة العلاج' : 'Digital signature on treatment plan' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span v-if="latestConsent" :class="[consentStatusColors[latestConsent.status], 'px-3 py-1 rounded-full text-xs font-medium']">
                        {{ consentStatusLabel(latestConsent.status) }}
                    </span>
                    <button
                        v-if="!hasSignedConsent && plan.status !== 'draft' && plan.status !== 'cancelled' && plan.status !== 'completed'"
                        @click="showConsentModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-[#C4A265] rounded-lg hover:bg-[#B39255] transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        {{ hasPendingConsent ? (isRtl ? 'إعادة الإرسال' : 'Resend') : (isRtl ? 'إرسال طلب موافقة' : 'Send Consent') }}
                    </button>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                <div v-if="!latestConsent" class="text-center py-4">
                    <p class="text-sm text-gray-500">{{ isRtl ? 'لم يتم إرسال طلب موافقة بعد' : 'No consent request sent yet' }}</p>
                    <p v-if="needsConsent" class="text-xs text-amber-500 mt-1">{{ isRtl ? 'يجب الحصول على موافقة المريض قبل بدء العلاج' : 'Patient consent required before starting' }}</p>
                </div>
                <div v-else-if="hasSignedConsent" class="flex items-center gap-3 p-4 bg-green-50 rounded-xl border border-green-100">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-700">{{ isRtl ? 'تم التوقيع بنجاح' : 'Consent Signed' }}</p>
                        <p class="text-xs text-green-600">{{ formatDate(latestConsent.signed_at) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <img v-if="latestConsent.signature_url" :src="latestConsent.signature_url" alt="Signature" class="h-10 rounded border border-green-200 bg-white p-1" />
                        <a v-if="latestConsent.id" :href="`/doctor/dental/consent/${latestConsent.id}/pdf`" target="_blank"
                           class="inline-flex items-center gap-1 px-2 py-1 text-xs text-[#C4A265] bg-[#C4A265]/5 rounded-lg hover:bg-[#C4A265]/10 border border-[#C4A265]/10 transition">
                            PDF
                        </a>
                    </div>
                </div>
                <div v-else-if="hasPendingConsent" class="p-4 bg-yellow-50 rounded-xl border border-yellow-100">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-yellow-400 rounded-full animate-pulse flex-shrink-0"></div>
                        <div>
                            <p class="text-sm font-medium text-yellow-700">{{ isRtl ? 'في انتظار توقيع المريض' : 'Waiting for patient signature' }}</p>
                            <p class="text-xs text-yellow-600">{{ isRtl ? 'تم الإرسال' : 'Sent' }} {{ formatDate(latestConsent.sent_at) }}</p>
                        </div>
                    </div>
                </div>
                <div v-else-if="latestConsent?.status === 'declined'" class="p-4 bg-red-50 rounded-xl border border-red-100">
                    <p class="text-sm font-medium text-red-700">{{ isRtl ? 'رفض المريض الموافقة' : 'Patient declined consent' }}</p>
                    <p v-if="latestConsent.declined_reason" class="text-xs text-red-600 mt-1">{{ latestConsent.declined_reason }}</p>
                </div>
            </div>
        </div>

        <!-- Send Consent Modal -->
        <Teleport to="body">
            <div v-if="showConsentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showConsentModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-800 mb-1">{{ isRtl ? 'إرسال طلب موافقة' : 'Send Consent Request' }}</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ isRtl ? 'سيتم إرسال طلب توقيع إلكتروني للمريض' : 'Digital signature request will be sent to patient' }}</p>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">{{ isRtl ? 'ملاحظات المخاطر (اختياري)' : 'Risks Notes (optional)' }}</label>
                            <textarea v-model="consentRisksNotes" class="w-full p-3 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" rows="3"></textarea>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 block mb-1">{{ isRtl ? 'مدة الصلاحية (بالأيام)' : 'Validity Period (days)' }}</label>
                            <input v-model.number="consentExpiryDays" type="number" min="1" max="30" class="w-full p-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button @click="sendConsent" :disabled="sendingConsent" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-[#C4A265] rounded-xl hover:bg-[#B39255] disabled:opacity-50 transition flex items-center justify-center gap-2">
                            <svg v-if="sendingConsent" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            {{ isRtl ? 'إرسال' : 'Send' }}
                        </button>
                        <button @click="showConsentModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Treatments Table -->
        <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay:0.25s">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">{{ isRtl ? 'العلاجات' : 'Treatments' }}</h2>
            </div>

            <div v-if="!plan.treatments || plan.treatments.length === 0" class="p-12 text-center">
                <div class="w-14 h-14 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-3 border border-gray-100">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                </div>
                <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد علاجات' : 'No treatments found' }}</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'السن' : 'Tooth' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'نوع العلاج' : 'Treatment Type' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">{{ isRtl ? 'الأسطح' : 'Surfaces' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">{{ isRtl ? 'تكلفة المختبر' : 'Lab Cost' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="treatment in plan.treatments" :key="treatment.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900">{{ treatment.tooth_number || '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ treatment.treatment_type ? (locale === 'ar' ? (treatment.treatment_type.name_ar || treatment.treatment_type.name_en) : (treatment.treatment_type.name_en || treatment.treatment_type.name_ar)) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 hidden sm:table-cell">{{ treatment.surfaces || '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate hidden md:table-cell">{{ treatment.description || '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 font-medium">{{ formatCurrency(treatment.cost) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 hidden sm:table-cell">{{ formatCurrency(treatment.lab_cost) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="[treatmentStatusColors[treatment.status] || 'bg-gray-100 text-gray-700', 'px-2.5 py-1 rounded-full text-xs font-medium']">
                                    {{ treatmentStatusLabels[treatment.status] || treatment.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Status Change Confirm Modal -->
        <ConfirmModal
            :show="showStatusModal"
            :title="isRtl ? 'تأكيد تغيير الحالة' : 'Confirm Status Change'"
            :message="isRtl ? 'هل أنت متأكد من تغيير حالة خطة العلاج؟' : 'Are you sure you want to change the treatment plan status?'"
            :confirmText="isRtl ? 'تأكيد' : 'Confirm'"
            :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
            type="info"
            @confirm="executeStatusChange"
            @cancel="showStatusModal = false"
        />

        <!-- Success Toast -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0"
        >
            <div v-if="showSuccess" class="fixed bottom-6 ltr:right-6 rtl:left-6 z-50 flex items-center gap-3 px-5 py-3 bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-200/50">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ successMessage }}</span>
            </div>
        </Transition>

        <!-- Error Toast -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0"
        >
            <div v-if="showErrorToast" class="fixed bottom-6 ltr:right-6 rtl:left-6 z-50 flex items-center gap-3 px-5 py-3 bg-red-600 text-white rounded-xl shadow-lg shadow-red-200/50">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ errorToastMsg }}</span>
            </div>
        </Transition>
    </div>
</template>

<style>
@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
</style>
