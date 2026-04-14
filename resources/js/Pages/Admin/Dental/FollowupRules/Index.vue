<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    rules: Array,
    upcomingFollowups: Array,
    stats: Object,
    treatmentTypes: Array,
    autoFollowupEnabled: Boolean,
});

const editingRule = ref(null);
const editForm = useForm({
    label_ar: '',
    label_en: '',
    followup_days: 7,
    auto_create_booking: true,
    sms_patient: true,
    notify_doctor: true,
    notify_secretary: true,
    sms_days_before: 1,
    is_active: true,
    notes: '',
});

const treatmentLabels = {
    filling: { ar: 'حشو', en: 'Filling' },
    extraction: { ar: 'خلع', en: 'Extraction' },
    surgical_extraction: { ar: 'خلع جراحي', en: 'Surgical Extraction' },
    root_canal: { ar: 'علاج عصب', en: 'Root Canal' },
    crown: { ar: 'تاج', en: 'Crown' },
    bridge: { ar: 'جسر', en: 'Bridge' },
    implant: { ar: 'زراعة', en: 'Implant' },
    cleaning: { ar: 'تنظيف', en: 'Cleaning' },
    scaling: { ar: 'تقليح', en: 'Scaling' },
    whitening: { ar: 'تبييض', en: 'Whitening' },
    veneer: { ar: 'قشرة', en: 'Veneer' },
    orthodontic: { ar: 'تقويم', en: 'Orthodontic' },
    denture: { ar: 'طقم أسنان', en: 'Denture' },
    sealant: { ar: 'مانع تسوس', en: 'Sealant' },
    fluoride: { ar: 'فلورايد', en: 'Fluoride' },
    gum_treatment: { ar: 'علاج لثة', en: 'Gum Treatment' },
    bone_graft: { ar: 'ترقيع عظم', en: 'Bone Graft' },
    sinus_lift: { ar: 'رفع جيب', en: 'Sinus Lift' },
    night_guard: { ar: 'واقي ليلي', en: 'Night Guard' },
    retainer: { ar: 'مثبت', en: 'Retainer' },
};

function treatmentLabel(type) {
    const l = treatmentLabels[type];
    return l ? (isRtl.value ? l.ar : l.en) : type;
}

function formatDays(days) {
    if (days >= 365) return isRtl.value ? `${Math.round(days/365)} سنة` : `${Math.round(days/365)} year`;
    if (days >= 30) return isRtl.value ? `${Math.round(days/30)} شهر` : `${Math.round(days/30)} month`;
    if (days >= 7) return isRtl.value ? `${Math.round(days/7)} أسبوع` : `${Math.round(days/7)} week`;
    return isRtl.value ? `${days} يوم` : `${days} days`;
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function openEdit(rule) {
    editingRule.value = rule;
    editForm.label_ar = rule.label_ar;
    editForm.label_en = rule.label_en;
    editForm.followup_days = rule.followup_days;
    editForm.auto_create_booking = rule.auto_create_booking;
    editForm.sms_patient = rule.sms_patient;
    editForm.notify_doctor = rule.notify_doctor;
    editForm.notify_secretary = rule.notify_secretary;
    editForm.sms_days_before = rule.sms_days_before;
    editForm.is_active = rule.is_active;
    editForm.notes = rule.notes || '';
}

function saveRule() {
    editForm.post(`/admin/dental/followup-rules/${editingRule.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { editingRule.value = null; },
    });
}

function toggleRule(rule) {
    router.post(`/admin/dental/followup-rules/${rule.id}/toggle`, {}, { preserveScroll: true });
}

function toggleGlobal() {
    router.post('/admin/dental/followup-rules-global/toggle', {}, { preserveScroll: true });
}

const showCancelModal = ref(false);
const pendingCancelId = ref(null);
const showResetModal = ref(false);

function cancelFollowup(id) {
    pendingCancelId.value = id;
    showCancelModal.value = true;
}

function executeCancelFollowup() {
    if (!pendingCancelId.value) return;
    router.post(`/admin/dental/scheduled-followups/${pendingCancelId.value}/cancel`, {}, { preserveScroll: true });
    showCancelModal.value = false;
    pendingCancelId.value = null;
}

function sendSms(id) {
    router.post(`/admin/dental/scheduled-followups/${id}/sms`, {}, { preserveScroll: true });
}

function seedDefaults() {
    showResetModal.value = true;
}

function executeSeedDefaults() {
    router.post('/admin/dental/followup-rules-seed', {}, { preserveScroll: true });
    showResetModal.value = false;
}

const followupStatusConfig = {
    pending: { bg: 'bg-amber-50', text: 'text-amber-700', label: { ar: 'معلق', en: 'Pending' } },
    booking_created: { bg: 'bg-blue-50', text: 'text-blue-700', label: { ar: 'تم الحجز', en: 'Booked' } },
    sms_sent: { bg: 'bg-emerald-50', text: 'text-emerald-700', label: { ar: 'تم الإرسال', en: 'SMS Sent' } },
    completed: { bg: 'bg-green-50', text: 'text-green-700', label: { ar: 'مكتمل', en: 'Completed' } },
    cancelled: { bg: 'bg-red-50', text: 'text-red-700', label: { ar: 'ملغي', en: 'Cancelled' } },
};

function statusLabel(status) {
    const c = followupStatusConfig[status] || followupStatusConfig.pending;
    return isRtl.value ? c.label.ar : c.label.en;
}
function statusClasses(status) {
    const c = followupStatusConfig[status] || followupStatusConfig.pending;
    return `${c.bg} ${c.text}`;
}

const activeTab = ref('rules');
</script>

<template>
    <AdminLayout :title="isRtl ? 'جدولة المتابعة التلقائية' : 'Auto Follow-up Scheduling'">
        <div class="space-y-6">
            <!-- Hero -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-600 via-cyan-700 to-teal-800 p-7">
                <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-cyan-400/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-teal-300/15 rounded-full blur-3xl"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">{{ isRtl ? 'جدولة المتابعة التلقائية' : 'Auto Follow-up Scheduling' }}</h1>
                            <p class="text-cyan-100/80 text-sm mt-0.5">{{ isRtl ? 'إعداد قواعد متابعة تلقائية حسب نوع العلاج' : 'Configure automatic follow-up rules per treatment type' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Global Toggle -->
                        <button @click="toggleGlobal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm"
                            :class="autoFollowupEnabled ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-white/20 text-white/70 hover:bg-white/30'">
                            <span class="w-2.5 h-2.5 rounded-full" :class="autoFollowupEnabled ? 'bg-white animate-pulse' : 'bg-white/40'"></span>
                            {{ autoFollowupEnabled ? (isRtl ? 'مفعّل' : 'Enabled') : (isRtl ? 'معطّل' : 'Disabled') }}
                        </button>
                        <button @click="seedDefaults"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            {{ isRtl ? 'إعادة تعيين' : 'Reset Defaults' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="dental-card-enter bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80" style="animation-delay:0.1s">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'قواعد مفعلة' : 'Active Rules' }}</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.active_rules }}<span class="text-sm font-normal text-gray-400"> / {{ stats.total_rules }}</span></p>
                </div>
                <div class="dental-card-enter bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80" style="animation-delay:0.15s">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'متابعات معلقة' : 'Pending Follow-ups' }}</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.pending_followups }}</p>
                </div>
                <div class="dental-card-enter bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80" style="animation-delay:0.2s">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'خلال أسبوع' : 'This Week' }}</p>
                    <p class="text-2xl font-bold text-cyan-600 mt-1">{{ stats.upcoming_week }}</p>
                </div>
                <div class="dental-card-enter bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80" style="animation-delay:0.25s">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="w-2.5 h-2.5 rounded-full" :class="autoFollowupEnabled ? 'bg-emerald-500 animate-pulse' : 'bg-red-400'"></span>
                        <span class="text-sm font-semibold" :class="autoFollowupEnabled ? 'text-emerald-600' : 'text-red-500'">
                            {{ autoFollowupEnabled ? (isRtl ? 'مفعّل' : 'Active') : (isRtl ? 'معطّل' : 'Disabled') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="dental-card-enter flex items-center gap-1 bg-white rounded-xl p-1 border border-gray-100 shadow-sm" style="animation-delay:0.3s">
                <button @click="activeTab = 'rules'"
                    :class="activeTab === 'rules' ? 'bg-cyan-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    {{ isRtl ? 'القواعد' : 'Rules' }} ({{ rules.length }})
                </button>
                <button @click="activeTab = 'upcoming'"
                    :class="activeTab === 'upcoming' ? 'bg-cyan-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    {{ isRtl ? 'المتابعات القادمة' : 'Upcoming' }} ({{ upcomingFollowups.length }})
                </button>
            </div>

            <!-- Rules Tab -->
            <div v-if="activeTab === 'rules'" class="dental-card-enter space-y-3" style="animation-delay:0.35s">
                <div v-for="rule in rules" :key="rule.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-5 hover:shadow-md transition-all duration-200"
                    :class="{ 'opacity-50': !rule.is_active }">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4 flex-1 min-w-0">
                            <!-- Treatment Type Badge -->
                            <div class="w-12 h-12 rounded-xl bg-cyan-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-gray-900">{{ isRtl ? rule.label_ar : rule.label_en }}</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500">{{ treatmentLabel(rule.treatment_type) }}</span>
                                </div>
                                <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ formatDays(rule.followup_days) }}
                                    </span>
                                    <span v-if="rule.auto_create_booking" class="flex items-center gap-1 text-blue-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ isRtl ? 'حجز تلقائي' : 'Auto-book' }}
                                    </span>
                                    <span v-if="rule.sms_patient" class="flex items-center gap-1 text-emerald-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                                        SMS
                                    </span>
                                    <span v-if="rule.notify_doctor" class="flex items-center gap-1 text-violet-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                        {{ isRtl ? 'تنبيه الطبيب' : 'Doctor' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button @click="openEdit(rule)" class="w-8 h-8 rounded-lg bg-gray-50 hover:bg-cyan-50 flex items-center justify-center text-gray-400 hover:text-cyan-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button @click="toggleRule(rule)"
                                class="w-8 h-8 rounded-lg flex items-center justify-center transition"
                                :class="rule.is_active ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-red-50 text-red-400 hover:bg-red-100'">
                                <svg v-if="rule.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Follow-ups Tab -->
            <div v-if="activeTab === 'upcoming'" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay:0.35s">
                <div class="overflow-x-auto">
                    <table v-if="upcomingFollowups.length" class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'العلاج' : 'Treatment' }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'تاريخ المتابعة' : 'Follow-up Date' }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحجز' : 'Booking' }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="f in upcomingFollowups" :key="f.id" class="border-b border-gray-50 hover:bg-cyan-50/30 transition-colors">
                                <td class="px-5 py-4">
                                    <Link v-if="f.patient" :href="`/admin/patients/${f.patient.id}`" class="text-sm font-medium text-gray-900 hover:text-cyan-600 transition">
                                        {{ f.patient.full_name }}
                                    </Link>
                                    <div v-if="f.patient" class="text-xs text-gray-400 font-mono mt-0.5">{{ f.patient.file_number }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-700">{{ isRtl ? f.rule?.label_ar : f.rule?.label_en }}</div>
                                    <div v-if="f.treatment" class="text-xs text-gray-400 mt-0.5">
                                        {{ treatmentLabel(f.treatment.treatment_type) }}
                                        <span v-if="f.treatment.tooth_number" class="text-cyan-500 font-mono"> #{{ f.treatment.tooth_number }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm font-medium" :class="new Date(f.scheduled_date) <= new Date() ? 'text-red-600' : 'text-gray-700'">
                                        {{ formatDate(f.scheduled_date) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="statusClasses(f.status)" class="px-2.5 py-1 rounded-full text-[11px] font-semibold">
                                        {{ statusLabel(f.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span v-if="f.booking" class="text-xs text-blue-600 font-mono">{{ f.booking.booking_number }}</span>
                                    <span v-else class="text-xs text-gray-300">-</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button v-if="f.status !== 'cancelled' && f.status !== 'completed'"
                                            @click="sendSms(f.id)" :title="isRtl ? 'إرسال SMS' : 'Send SMS'"
                                            class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 flex items-center justify-center text-emerald-600 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                                        </button>
                                        <button v-if="f.status !== 'cancelled' && f.status !== 'completed'"
                                            @click="cancelFollowup(f.id)" :title="isRtl ? 'إلغاء' : 'Cancel'"
                                            class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center text-red-500 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="p-16 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد متابعات قادمة' : 'No upcoming follow-ups' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Rule Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="editingRule" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="editingRule = null"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-5 z-10 max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-cyan-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'تعديل القاعدة' : 'Edit Rule' }}</h3>
                                <p class="text-sm text-gray-500">{{ treatmentLabel(editingRule.treatment_type) }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="saveRule" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'الاسم بالعربي' : 'Arabic Label' }}</label>
                                    <input v-model="editForm.label_ar" type="text" class="dental-input" required />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'الاسم بالإنجليزي' : 'English Label' }}</label>
                                    <input v-model="editForm.label_en" type="text" class="dental-input" required />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'أيام المتابعة' : 'Follow-up Days' }}</label>
                                    <input v-model="editForm.followup_days" type="number" min="1" max="365" class="dental-input" required />
                                    <p class="text-xs text-gray-400 mt-1">= {{ formatDays(editForm.followup_days) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'تذكير SMS قبل (أيام)' : 'SMS Reminder Before (days)' }}</label>
                                    <input v-model="editForm.sms_days_before" type="number" min="0" max="7" class="dental-input" />
                                </div>
                            </div>

                            <div class="space-y-3 pt-2">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input v-model="editForm.auto_create_booking" type="checkbox" class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500" />
                                    <div>
                                        <span class="text-sm text-gray-700 font-medium group-hover:text-cyan-600 transition">{{ isRtl ? 'إنشاء حجز تلقائي' : 'Auto-create booking' }}</span>
                                        <p class="text-xs text-gray-400">{{ isRtl ? 'سيتم إنشاء حجز متابعة تلقائياً عند اكتمال العلاج' : 'Automatically creates a follow-up booking when treatment completes' }}</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input v-model="editForm.sms_patient" type="checkbox" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500" />
                                    <div>
                                        <span class="text-sm text-gray-700 font-medium group-hover:text-emerald-600 transition">{{ isRtl ? 'إرسال SMS للمريض' : 'Send SMS to patient' }}</span>
                                        <p class="text-xs text-gray-400">{{ isRtl ? 'تنبيه المريض بموعد المتابعة عبر رسالة نصية' : 'Notify patient about follow-up date via SMS' }}</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input v-model="editForm.notify_doctor" type="checkbox" class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500" />
                                    <span class="text-sm text-gray-700 font-medium group-hover:text-violet-600 transition">{{ isRtl ? 'تنبيه الطبيب' : 'Notify doctor' }}</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input v-model="editForm.notify_secretary" type="checkbox" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" />
                                    <span class="text-sm text-gray-700 font-medium group-hover:text-amber-600 transition">{{ isRtl ? 'تنبيه السكرتير' : 'Notify secretary' }}</span>
                                </label>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <textarea v-model="editForm.notes" rows="2" class="dental-input" style="resize:none"></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" @click="editingRule = null"
                                    class="px-5 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                                <button type="submit" :disabled="editForm.processing"
                                    class="px-6 py-2 text-sm font-semibold text-white bg-gradient-to-r from-cyan-500 to-teal-500 rounded-xl hover:from-cyan-600 hover:to-teal-600 disabled:opacity-50 transition-all shadow-lg shadow-cyan-200/40">
                                    {{ editForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ التغييرات' : 'Save Changes') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Cancel Follow-up Confirm Modal -->
        <ConfirmModal
            :show="showCancelModal"
            :title="isRtl ? 'إلغاء المتابعة' : 'Cancel Follow-up'"
            :message="isRtl ? 'هل أنت متأكد من إلغاء هذه المتابعة؟' : 'Are you sure you want to cancel this follow-up?'"
            :confirmText="isRtl ? 'إلغاء المتابعة' : 'Cancel Follow-up'"
            :cancelText="isRtl ? 'تراجع' : 'Go Back'"
            confirmColor="red"
            @confirm="executeCancelFollowup"
            @cancel="showCancelModal = false"
        />

        <!-- Reset Defaults Confirm Modal -->
        <ConfirmModal
            :show="showResetModal"
            :title="isRtl ? 'إعادة تعيين القواعد' : 'Reset Default Rules'"
            :message="isRtl ? 'سيتم إعادة تعيين القواعد الافتراضية. هل تريد المتابعة؟' : 'This will reset to the default follow-up rules. Continue?'"
            :confirmText="isRtl ? 'إعادة تعيين' : 'Reset'"
            :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
            confirmColor="amber"
            @confirm="executeSeedDefaults"
            @cancel="showResetModal = false"
        />
    </AdminLayout>
</template>

<style scoped>
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

.dental-input {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    background: rgba(249, 250, 251, 0.5);
    transition: all 0.2s;
}
.dental-input:focus {
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(34, 211, 238, 0.3);
    border-color: #67e8f9;
}
</style>
