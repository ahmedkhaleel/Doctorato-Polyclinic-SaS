<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { t } = useLocale();
const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

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
    pending: 'bg-[#F5E7C8]/60 text-[#8B7043]',
    approved: 'bg-slate-100 text-[#0F2444]',
    in_progress: 'bg-slate-100 text-[#0F2444]',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800',
};

const treatmentStatusColors = {
    planned: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-slate-100 text-[#1B365D]',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-red-100 text-red-700',
};

const priorityColors = {
    low: 'bg-gray-100 text-gray-600',
    normal: 'bg-slate-100 text-[#1B365D]',
    high: 'bg-[#F5E7C8]/60 text-[#8B7043]',
    urgent: 'bg-red-100 text-red-700',
};

const progressPercent = computed(() => {
    if (!props.plan.estimated_sessions || props.plan.estimated_sessions === 0) return 0;
    return Math.min(Math.round((props.plan.completed_sessions / props.plan.estimated_sessions) * 100), 100);
});

const totalTreatmentsCost = computed(() => {
    if (!props.plan.treatments) return 0;
    return props.plan.treatments.reduce((sum, tr) => sum + (parseFloat(tr.cost) || 0) + (parseFloat(tr.lab_cost) || 0), 0);
});

const totalTreatmentOnlyCost = computed(() => {
    if (!props.plan.treatments) return 0;
    return props.plan.treatments.reduce((sum, tr) => sum + (parseFloat(tr.cost) || 0), 0);
});

const totalLabCost = computed(() => {
    if (!props.plan.treatments) return 0;
    return props.plan.treatments.reduce((sum, tr) => sum + (parseFloat(tr.lab_cost) || 0), 0);
});

const completedTreatmentsCount = computed(() => props.plan.treatments?.filter(t => t.status === 'completed').length || 0);
const inProgressTreatmentsCount = computed(() => props.plan.treatments?.filter(t => t.status === 'in_progress').length || 0);
const plannedTreatmentsCount = computed(() => props.plan.treatments?.filter(t => t.status === 'planned').length || 0);

const updatingStatus = ref(false);

// ─── Toast ──────────────────────────────────────────────
const showSuccess = ref(false);
const successMessage = ref('');
const showError = ref(false);
const errorMessage = ref('');
watch(() => page.props.flash?.success, (msg) => {
    if (msg) { successMessage.value = msg; showSuccess.value = true; setTimeout(() => { showSuccess.value = false; }, 4000); }
});
watch(() => page.props.flash?.error, (msg) => {
    if (msg) { errorMessage.value = msg; showError.value = true; setTimeout(() => { showError.value = false; }, 5000); }
});

// ─── Confirm Modals ─────────────────────────────────────
const showStatusModal = ref(false);
const pendingStatus = ref(null);
const showDeleteModal = ref(false);
const showResendModal = ref(false);
const pendingConsentId = ref(null);

function confirmStatusChange(newStatus) {
    pendingStatus.value = newStatus;
    showStatusModal.value = true;
}

function executeStatusChange() {
    if (!pendingStatus.value) return;
    updatingStatus.value = true;
    showStatusModal.value = false;
    router.post(`/admin/dental/treatment-plans/${props.plan.id}/status`, {
        status: pendingStatus.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            updatingStatus.value = false;
            pendingStatus.value = null;
        },
    });
}

function confirmDelete() {
    showDeleteModal.value = true;
}

function executeDelete() {
    showDeleteModal.value = false;
    router.post(`/admin/dental/treatment-plans/${props.plan.id}/delete`);
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
const needsConsent = computed(() => {
    return props.plan.status === 'approved' && !hasSignedConsent.value;
});

const consentStatusColors = {
    pending: 'bg-[#F5E7C8]/60 text-[#8B7043]',
    signed: 'bg-emerald-100 text-emerald-700',
    declined: 'bg-red-100 text-red-700',
    expired: 'bg-gray-100 text-gray-600',
};

const consentStatusLabels = {
    pending: { ar: 'في انتظار التوقيع', en: 'Pending Signature' },
    signed: { ar: 'تم التوقيع', en: 'Signed' },
    declined: { ar: 'مرفوض', en: 'Declined' },
    expired: { ar: 'منتهي الصلاحية', en: 'Expired' },
};

function consentStatusLabel(status) {
    const l = consentStatusLabels[status];
    return l ? (locale.value === 'ar' ? l.ar : l.en) : status;
}

function sendConsent() {
    sendingConsent.value = true;
    router.post(`/admin/dental/treatment-plans/${props.plan.id}/consent/send`, {
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

function confirmResendConsent(consentId) {
    pendingConsentId.value = consentId;
    showResendModal.value = true;
}

function executeResendConsent() {
    if (!pendingConsentId.value) return;
    showResendModal.value = false;
    router.post(`/admin/dental/consent/${pendingConsentId.value}/resend`, {}, { preserveScroll: true });
    pendingConsentId.value = null;
}
</script>

<template>
    <AdminLayout :title="$t('a_treatment_plan_details')">
        <div class="space-y-6">
            <!-- Hero Header -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-7">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="absolute -top-12 ltr:-right-12 rtl:-left-12 w-48 h-48 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-8 ltr:left-20 rtl:right-20 w-32 h-32 bg-[#2C4E7A]/10 rounded-full blur-2xl"></div>

                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link href="/admin/dental/treatment-plans" class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center hover:bg-white/20 transition ring-1 ring-white/15">
                            <svg class="w-5 h-5 text-white rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </Link>
                        <div>
                            <p class="text-slate-200/80 text-xs font-semibold tracking-wider uppercase">{{ $t('a_treatment_plan') }} #{{ plan.id }}</p>
                            <h1 class="text-xl sm:text-2xl font-bold text-white mt-0.5">
                                {{ locale === 'ar' ? (plan.title_ar || plan.title_en || `#${plan.id}`) : (plan.title_en || plan.title_ar || `#${plan.id}`) }}
                            </h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <Link v-if="plan.patient" :href="`/admin/dental/chart/${plan.patient.id}`"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white/90 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 border border-white/15 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            {{ $t('a_dental_chart') }}
                        </Link>
                        <a :href="`/admin/dental/treatment-plans/${plan.id}/pdf`" target="_blank"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white/90 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 border border-white/15 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Plan Info + Progress -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="dental-card-enter lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6 space-y-5" style="animation-delay: 0.1s">
                    <div class="flex items-start justify-between">
                        <div>
                            <span :class="[statusColors[plan.status] || 'bg-gray-100 text-gray-800', 'px-3 py-1 rounded-full text-sm font-medium']">
                                {{ $t('a_plan_status_' + plan.status) }}
                            </span>
                            <span :class="[priorityColors[plan.priority] || 'bg-gray-100 text-gray-600', 'px-3 py-1 rounded-full text-sm font-medium ms-2']">
                                {{ $t('a_priority_' + (plan.priority || 'normal')) }}
                            </span>
                        </div>
                        <button @click="confirmDelete" class="text-gray-400 hover:text-red-500 transition p-1" aria-label="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">{{ $t('a_sessions_progress') }}</span>
                            <span class="text-sm text-gray-500">{{ plan.completed_sessions }}/{{ plan.estimated_sessions }} ({{ progressPercent }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div
                                class="h-3 rounded-full transition-all bg-[#1B365D]"
                                :style="{ width: progressPercent + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 block">{{ $t('a_patient') }}</span>
                            <Link v-if="plan.patient" :href="`/admin/patients/${plan.patient.id}`" class="font-medium text-[#1B365D] hover:underline">
                                {{ plan.patient.full_name }}
                            </Link>
                        </div>
                        <div>
                            <span class="text-gray-500 block">{{ $t('a_doctor') }}</span>
                            <span class="font-medium text-gray-900">{{ plan.doctor ? (locale === 'ar' ? plan.doctor.name_ar : plan.doctor.name_en) : '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 block">{{ $t('a_estimated_cost') }}</span>
                            <span class="font-medium text-gray-900">{{ formatCurrency(plan.estimated_cost) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 block">{{ $t('a_start_date') }}</span>
                            <span class="font-medium text-gray-900">{{ formatDate(plan.start_date) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 block">{{ $t('a_expected_end_date') }}</span>
                            <span class="font-medium text-gray-900">{{ formatDate(plan.expected_end_date) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 block">{{ $t('a_created_at') }}</span>
                            <span class="font-medium text-gray-900">{{ formatDate(plan.created_at) }}</span>
                        </div>
                    </div>

                    <div v-if="plan.description" class="pt-3 border-t">
                        <span class="text-sm text-gray-500 block mb-1">{{ $t('a_description') }}</span>
                        <p class="text-sm text-gray-700">{{ plan.description }}</p>
                    </div>

                    <div v-if="plan.notes" class="pt-3 border-t">
                        <span class="text-sm text-gray-500 block mb-1">{{ $t('a_notes') }}</span>
                        <p class="text-sm text-gray-700">{{ plan.notes }}</p>
                    </div>
                </div>

                <!-- Status Actions -->
                <div class="dental-card-enter bg-white rounded-xl shadow-sm border p-6 space-y-4" style="animation-delay:0.15s">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_actions') }}</h3>

                    <div class="space-y-2">
                        <button
                            v-if="plan.status === 'draft' || plan.status === 'pending'"
                            @click="confirmStatusChange('approved')"
                            :disabled="updatingStatus"
                            class="w-full px-4 py-2 text-sm font-medium text-white bg-[#1B365D] rounded-lg hover:bg-[#1B365D] disabled:opacity-50 transition"
                        >
                            {{ $t('a_approve_plan') }}
                        </button>
                        <button
                            v-if="plan.status === 'approved' && hasSignedConsent"
                            @click="confirmStatusChange('in_progress')"
                            :disabled="updatingStatus"
                            class="w-full px-4 py-2 text-sm font-medium text-white bg-[#1B365D] rounded-lg hover:bg-[#1B365D] disabled:opacity-50 transition"
                        >
                            {{ $t('a_start_plan') }}
                        </button>
                        <div v-else-if="plan.status === 'approved' && !hasSignedConsent" class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <p class="text-xs text-amber-700 font-medium flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                {{ locale === 'ar' ? 'يجب توقيع المريض قبل بدء العلاج' : 'Patient must sign consent before starting' }}
                            </p>
                        </div>
                        <button
                            v-if="plan.status === 'in_progress'"
                            @click="confirmStatusChange('completed')"
                            :disabled="updatingStatus"
                            class="w-full px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition"
                        >
                            {{ $t('a_complete_plan') }}
                        </button>
                        <button
                            v-if="plan.status !== 'completed' && plan.status !== 'cancelled'"
                            @click="confirmStatusChange('cancelled')"
                            :disabled="updatingStatus"
                            class="w-full px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 disabled:opacity-50 transition"
                        >
                            {{ $t('a_cancel_plan') }}
                        </button>
                    </div>

                    <div class="pt-4 border-t">
                        <div class="text-sm text-gray-500 mb-1">{{ $t('a_total_treatments_cost') }}</div>
                        <div class="text-2xl font-bold text-[#1B365D]">{{ formatCurrency(totalTreatmentsCost) }}</div>
                    </div>
                </div>
            </div>

            <!-- Consent Section -->
            <div class="dental-card-enter bg-white rounded-xl shadow-sm border overflow-hidden" style="animation-delay:0.2s">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                             :class="hasSignedConsent ? 'bg-emerald-100' : hasPendingConsent ? 'bg-[#F5E7C8]/60' : 'bg-gray-100'">
                            <svg v-if="hasSignedConsent" class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <svg v-else-if="hasPendingConsent" class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">{{ locale === 'ar' ? 'موافقة المريض' : 'Patient Consent' }}</h2>
                            <p class="text-xs text-gray-500">
                                {{ locale === 'ar' ? 'التوقيع الرقمي على خطة العلاج' : 'Digital signature on treatment plan' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Consent Status Badge -->
                        <span v-if="latestConsent" :class="[consentStatusColors[latestConsent.status], 'px-3 py-1 rounded-full text-xs font-medium']">
                            {{ consentStatusLabel(latestConsent.status) }}
                        </span>

                        <!-- Send / Resend Button -->
                        <button
                            v-if="!hasSignedConsent && plan.status !== 'draft' && plan.status !== 'cancelled' && plan.status !== 'completed'"
                            @click="showConsentModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-[#1B365D] rounded-lg hover:bg-[#1B365D] transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            {{ hasPendingConsent ? (locale === 'ar' ? 'إعادة الإرسال' : 'Resend') : (locale === 'ar' ? 'إرسال طلب موافقة' : 'Send Consent') }}
                        </button>
                    </div>
                </div>

                <!-- Consent Body -->
                <div class="p-6">
                    <!-- No consent yet -->
                    <div v-if="!latestConsent" class="text-center py-6">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <p class="text-sm text-gray-500">
                            {{ locale === 'ar' ? 'لم يتم إرسال طلب موافقة بعد' : 'No consent request sent yet' }}
                        </p>
                        <p v-if="needsConsent" class="text-xs text-amber-500 mt-1">
                            {{ locale === 'ar' ? 'يجب الحصول على موافقة المريض قبل بدء العلاج' : 'Patient consent is required before starting treatment' }}
                        </p>
                    </div>

                    <!-- Signed consent details -->
                    <div v-else-if="hasSignedConsent" class="space-y-4">
                        <div class="flex items-center gap-3 p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                            <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-emerald-700">
                                    {{ locale === 'ar' ? 'تم التوقيع بنجاح' : 'Consent Signed Successfully' }}
                                </p>
                                <p class="text-xs text-emerald-600">
                                    {{ locale === 'ar' ? 'تم التوقيع بتاريخ' : 'Signed on' }} {{ formatDate(latestConsent.signed_at) }}
                                    <span v-if="latestConsent.patient_ip" class="text-emerald-500"> &bull; IP: {{ latestConsent.patient_ip }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <img v-if="latestConsent.signature_url" :src="latestConsent.signature_url" alt="Signature" class="h-10 rounded border border-emerald-200 bg-white p-1" />
                                <a v-if="latestConsent.pdf_url" :href="`/admin/dental/consent/${latestConsent.id}/pdf`" target="_blank"
                                   class="inline-flex items-center gap-1 px-2 py-1 text-xs text-[#1B365D] bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Pending consent info -->
                    <div v-else-if="hasPendingConsent" class="p-4 bg-[#F5E7C8]/40 rounded-xl border border-[#F5E7C8]/60">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-3 h-3 bg-[#D4B57E] rounded-full animate-pulse"></div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#8B7043]">
                                    {{ locale === 'ar' ? 'في انتظار توقيع المريض' : 'Waiting for patient signature' }}
                                </p>
                                <p class="text-xs text-[#C4A265]">
                                    {{ locale === 'ar' ? 'تم الإرسال بتاريخ' : 'Sent on' }} {{ formatDate(latestConsent.sent_at) }}
                                    <span v-if="latestConsent.expires_at"> &bull; {{ locale === 'ar' ? 'ينتهي' : 'Expires' }} {{ formatDate(latestConsent.expires_at) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Declined consent -->
                    <div v-else-if="latestConsent?.status === 'declined'" class="p-4 bg-red-50 rounded-xl border border-red-100">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-red-700">
                                    {{ locale === 'ar' ? 'رفض المريض الموافقة' : 'Patient declined the consent' }}
                                </p>
                                <p v-if="latestConsent.declined_reason" class="text-xs text-red-600 mt-1">
                                    {{ locale === 'ar' ? 'السبب:' : 'Reason:' }} {{ latestConsent.declined_reason }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Consent History -->
                    <div v-if="consentHistory.length > 1" class="mt-4 pt-4 border-t">
                        <p class="text-xs font-medium text-gray-500 mb-2">{{ locale === 'ar' ? 'سجل الموافقات' : 'Consent History' }}</p>
                        <div class="space-y-1.5">
                            <div v-for="c in consentHistory" :key="c.id" class="flex items-center justify-between text-xs py-1.5 px-3 bg-gray-50 rounded-lg">
                                <span :class="[consentStatusColors[c.status], 'px-2 py-0.5 rounded-full text-[10px] font-medium']">
                                    {{ consentStatusLabel(c.status) }}
                                </span>
                                <span class="text-gray-400">{{ formatDate(c.sent_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Send Consent Modal -->
            <Teleport to="body">
                <div v-if="showConsentModal" v-focus-trap="() => (showConsentModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showConsentModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
                        <h3 class="text-lg font-bold text-gray-800 mb-1">{{ locale === 'ar' ? 'إرسال طلب موافقة' : 'Send Consent Request' }}</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            {{ locale === 'ar' ? 'سيتم إرسال طلب توقيع إلكتروني للمريض' : 'A digital signature request will be sent to the patient' }}
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">
                                    {{ locale === 'ar' ? 'ملاحظات المخاطر (اختياري)' : 'Risks Notes (optional)' }}
                                </label>
                                <textarea
                                    v-model="consentRisksNotes"
                                    class="doctorato-input w-full p-3 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300"
                                    rows="3"
                                    :placeholder="locale === 'ar' ? 'أضف أي ملاحظات عن المخاطر المحتملة...' : 'Add any notes about potential risks...'"
                                ></textarea>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1">
                                    {{ locale === 'ar' ? 'مدة الصلاحية (بالأيام)' : 'Validity Period (days)' }}
                                </label>
                                <input
                                    v-model.number="consentExpiryDays"
                                    type="number"
                                    min="1"
                                    max="30"
                                    class="doctorato-input w-full p-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300"
                                />
                            </div>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button
                                @click="sendConsent"
                                :disabled="sendingConsent"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-[#1B365D] rounded-xl hover:bg-[#1B365D] disabled:opacity-50 transition flex items-center justify-center gap-2"
                            >
                                <svg v-if="sendingConsent" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ locale === 'ar' ? 'إرسال' : 'Send' }}
                            </button>
                            <button
                                @click="showConsentModal = false"
                                class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition"
                            >
                                {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Treatments with Visual Timeline -->
            <div class="dental-card-enter bg-white rounded-xl shadow-sm border overflow-hidden" style="animation-delay:0.25s">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">{{ $t('a_treatments') }}</h2>
                    <!-- Mini Status Summary -->
                    <div v-if="plan.treatments?.length" class="flex items-center gap-2">
                        <span v-if="completedTreatmentsCount > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ completedTreatmentsCount }}
                        </span>
                        <span v-if="inProgressTreatmentsCount > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-50 text-[#1B365D]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#1B365D]"></span> {{ inProgressTreatmentsCount }}
                        </span>
                        <span v-if="plannedTreatmentsCount > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> {{ plannedTreatmentsCount }}
                        </span>
                    </div>
                </div>

                <div v-if="!plan.treatments || plan.treatments.length === 0" class="p-8 text-center text-gray-400 text-sm">
                    {{ $t('a_no_treatments_found') }}
                </div>

                <!-- Treatment Cards -->
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="(treatment, idx) in plan.treatments" :key="treatment.id"
                        class="flex items-stretch hover:bg-gray-50/50 transition-colors">
                        <!-- Timeline Dot -->
                        <div class="flex flex-col items-center w-12 py-4 flex-shrink-0">
                            <div class="w-3 h-3 rounded-full border-2 flex-shrink-0"
                                :class="{
                                    'bg-emerald-500 border-emerald-500': treatment.status === 'completed',
                                    'bg-[#1B365D] border-[#1B365D] animate-pulse': treatment.status === 'in_progress',
                                    'bg-white border-gray-300': treatment.status === 'planned',
                                    'bg-red-400 border-red-400': treatment.status === 'cancelled',
                                }"></div>
                            <div v-if="idx < plan.treatments.length - 1" class="flex-1 w-0.5 mt-1"
                                :class="treatment.status === 'completed' ? 'bg-emerald-200' : 'bg-gray-200'"></div>
                        </div>
                        <!-- Card Content -->
                        <div class="flex-1 py-4 pe-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span v-if="treatment.tooth_number" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-[#1B365D] font-mono font-bold text-sm border border-slate-100">
                                        {{ treatment.tooth_number }}
                                    </span>
                                    <div>
                                        <span class="text-sm font-semibold text-gray-800">
                                            {{ treatment.treatment_type ? (locale === 'ar' ? (treatment.treatment_type.name_ar || treatment.treatment_type.name_en) : (treatment.treatment_type.name_en || treatment.treatment_type.name_ar)) : (treatment.treatment_type_raw || '-') }}
                                        </span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span v-if="treatment.surfaces && treatment.surfaces.length" class="text-[10px] text-gray-400">
                                                {{ Array.isArray(treatment.surfaces) ? treatment.surfaces.join(', ') : treatment.surfaces }}
                                            </span>
                                            <span v-if="treatment.description" class="text-[10px] text-gray-400">{{ treatment.description }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <div class="text-end">
                                        <span class="text-sm font-semibold text-gray-800">{{ formatCurrency(parseFloat(treatment.cost || 0) + parseFloat(treatment.lab_cost || 0)) }}</span>
                                        <div v-if="parseFloat(treatment.lab_cost || 0) > 0" class="text-[10px] text-gray-400">
                                            {{ locale === 'ar' ? 'معمل:' : 'Lab:' }} {{ formatCurrency(treatment.lab_cost) }}
                                        </div>
                                    </div>
                                    <span :class="[treatmentStatusColors[treatment.status] || 'bg-gray-100 text-gray-700', 'px-2 py-1 rounded-full text-[10px] font-semibold whitespace-nowrap']">
                                        {{ $t('a_treatment_status_' + (treatment.status || 'planned')) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cost Summary Footer -->
                <div v-if="plan.treatments?.length" class="px-6 py-4 bg-gray-50 border-t">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">{{ locale === 'ar' ? 'إجمالي العلاج' : 'Treatment Cost' }}</span>
                            <span class="text-sm font-bold text-gray-800">{{ formatCurrency(totalTreatmentOnlyCost) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">{{ locale === 'ar' ? 'تكلفة المعمل' : 'Lab Cost' }}</span>
                            <span class="text-sm font-bold text-gray-800">{{ formatCurrency(totalLabCost) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">{{ locale === 'ar' ? 'الإجمالي' : 'Total' }}</span>
                            <span class="text-lg font-bold text-[#1B365D]">{{ formatCurrency(totalTreatmentsCost) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">{{ locale === 'ar' ? 'التقديري' : 'Estimated' }}</span>
                            <span class="text-sm font-bold" :class="totalTreatmentsCost > parseFloat(plan.estimated_cost || 0) ? 'text-red-600' : 'text-emerald-600'">
                                {{ formatCurrency(plan.estimated_cost) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Change Confirm Modal -->
        <ConfirmModal
            :show="showStatusModal"
            :title="locale === 'ar' ? 'تأكيد تغيير الحالة' : 'Confirm Status Change'"
            :message="locale === 'ar' ? 'هل أنت متأكد من تغيير حالة خطة العلاج؟' : 'Are you sure you want to change the treatment plan status?'"
            :confirmText="locale === 'ar' ? 'تأكيد' : 'Confirm'"
            :cancelText="locale === 'ar' ? 'إلغاء' : 'Cancel'"
            confirmColor="cyan"
            @confirm="executeStatusChange"
            @cancel="showStatusModal = false"
        />

        <!-- Delete Confirm Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="locale === 'ar' ? 'حذف خطة العلاج' : 'Delete Treatment Plan'"
            :message="locale === 'ar' ? 'هل أنت متأكد من حذف خطة العلاج؟ سيتم حذف جميع العلاجات المرتبطة بها. لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this treatment plan? All associated treatments will be removed. This action cannot be undone.'"
            :confirmText="locale === 'ar' ? 'حذف' : 'Delete'"
            :cancelText="locale === 'ar' ? 'إلغاء' : 'Cancel'"
            confirmColor="red"
            @confirm="executeDelete"
            @cancel="showDeleteModal = false"
        />

        <!-- Resend Consent Confirm Modal -->
        <ConfirmModal
            :show="showResendModal"
            :title="locale === 'ar' ? 'إعادة إرسال طلب الموافقة' : 'Resend Consent Request'"
            :message="locale === 'ar' ? 'هل تريد إعادة إرسال طلب الموافقة للمريض؟' : 'Do you want to resend the consent request to the patient?'"
            :confirmText="locale === 'ar' ? 'إعادة الإرسال' : 'Resend'"
            :cancelText="locale === 'ar' ? 'إلغاء' : 'Cancel'"
            confirmColor="cyan"
            @confirm="executeResendConsent"
            @cancel="showResendModal = false"
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
            <div v-if="showError" class="fixed bottom-6 ltr:right-6 rtl:left-6 z-50 flex items-center gap-3 px-5 py-3 bg-red-600 text-white rounded-xl shadow-lg shadow-red-200/50">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ errorMessage }}</span>
            </div>
        </Transition>
    </AdminLayout>
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
