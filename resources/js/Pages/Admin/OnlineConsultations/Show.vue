<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    consultation: { type: Object, required: true },
});

const c = computed(() => props.consultation || {});
const patient = computed(() => c.value.patient || {});
const doctor = computed(() => c.value.doctor || {});
const visit = computed(() => c.value.visit || null);
const transactions = computed(() => c.value.payment_transactions || c.value.paymentTransactions || []);

/* ── Status ─────────────────────────────────────────────── */
const statusConfig = {
    scheduled:       { bg: 'bg-gray-100',   text: 'text-gray-700',    dot: 'bg-gray-400',   ar: 'مجدول',        en: 'Scheduled' },
    waiting:         { bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',  ar: 'في الانتظار',  en: 'Waiting', pulse: true },
    in_progress:     { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500',ar: 'جارية',        en: 'In Progress', pulse: true },
    completed:       { bg: 'bg-emerald-600',text: 'text-white',       dot: 'bg-white',      ar: 'مكتمل',        en: 'Completed' },
    cancelled:       { bg: 'bg-gray-50',    text: 'text-gray-500',    dot: 'bg-gray-300',   ar: 'ملغى',         en: 'Cancelled' },
    missed_patient:  { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',    ar: 'المريض غاب',   en: 'Patient Missed' },
    missed_doctor:   { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',    ar: 'الطبيب غاب',   en: 'Doctor Missed' },
    refunded:        { bg: 'bg-slate-50',    text: 'text-[#1B365D]',    dot: 'bg-[#1B365D]',   ar: 'مسترد',        en: 'Refunded' },
};
function statusStyle(s) { return statusConfig[s] || statusConfig.scheduled; }
function statusLabel(s) { const cc = statusConfig[s]; return cc ? (isRtl.value ? cc.ar : cc.en) : (s || '-'); }

const paymentConfig = {
    paid:     { bg: 'bg-emerald-50', text: 'text-emerald-700', ar: 'مدفوع',   en: 'Paid' },
    pending:  { bg: 'bg-amber-50',   text: 'text-amber-700',   ar: 'معلق',    en: 'Pending' },
    failed:   { bg: 'bg-red-50',     text: 'text-red-700',     ar: 'فشل',     en: 'Failed' },
    refunded: { bg: 'bg-slate-50',    text: 'text-[#1B365D]',    ar: 'مسترد',   en: 'Refunded' },
};
function paymentStyle(s) { return paymentConfig[s] || paymentConfig.pending; }
function paymentLabel(s) { const cc = paymentConfig[s]; return cc ? (isRtl.value ? cc.ar : cc.en) : (s || '-'); }

/* ── Helpers ────────────────────────────────────────────── */
function formatMoney(v) { return `${Number(v || 0).toLocaleString()} EGP`; }
function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
function formatDateTime(d) {
    if (!d) return '-';
    return new Date(d).toLocaleString(isRtl.value ? 'ar-EG' : 'en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
function formatTime(t) {
    if (!t) return '-';
    const s = String(t);
    if (s.length <= 8) return s.slice(0, 5);
    try { return new Date(s).toLocaleTimeString(isRtl.value ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit' }); }
    catch { return s; }
}
function doctorName(d) { return isRtl.value ? (d.name_ar || d.name_en || '-') : (d.name_en || d.name_ar || '-'); }
function doctorSpecialty(d) { return isRtl.value ? (d.specialization_ar || '') : (d.specialization_en || ''); }
function initials(name) {
    if (!name) return '؟';
    return String(name).trim().split(/\s+/).slice(0, 2).map(s => s.charAt(0)).join('').toUpperCase();
}
function photoUrl(d) {
    if (!d?.photo) return null;
    if (String(d.photo).startsWith('http')) return d.photo;
    return `/storage/${d.photo}`;
}
function genderLabel(g) {
    if (!g) return '-';
    if (g === 'male' || g === 'M') return isRtl.value ? 'ذكر' : 'Male';
    if (g === 'female' || g === 'F') return isRtl.value ? 'أنثى' : 'Female';
    return g;
}

const timelineItems = computed(() => [
    { labelAr: 'أُنشئت',       labelEn: 'Created',         value: formatDateTime(c.value.created_at) },
    { labelAr: 'الموعد المحدد', labelEn: 'Scheduled',       value: `${formatDate(c.value.scheduled_date)} · ${formatTime(c.value.start_time)}` },
    { labelAr: 'انضمام الطبيب', labelEn: 'Doctor joined',   value: formatDateTime(c.value.doctor_joined_at) },
    { labelAr: 'انضمام المريض', labelEn: 'Patient joined',  value: formatDateTime(c.value.patient_joined_at) },
    { labelAr: 'بدء الجلسة',    labelEn: 'Session started', value: formatDateTime(c.value.session_started_at || c.value.started_at) },
    { labelAr: 'نهاية الجلسة',  labelEn: 'Session ended',   value: formatDateTime(c.value.session_ended_at || c.value.ended_at) },
    { labelAr: 'المدة',         labelEn: 'Duration',        value: c.value.duration_minutes ? `${c.value.duration_minutes} ${isRtl.value ? 'دقيقة' : 'min'}` : '-' },
]);
</script>

<template>
    <div class="space-y-6 pb-12">
        <!-- Back breadcrumb -->
        <div class="flex items-center gap-2 text-sm">
            <Link href="/admin/online-consultations" class="inline-flex items-center gap-1.5 text-[#1B365D] hover:text-[#C4A265] transition">
                <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                {{ isRtl ? 'الاستشارات الأونلاين' : 'Online Consultations' }}
            </Link>
            <span class="text-gray-300">/</span>
            <span class="text-gray-500">{{ c.consultation_number || `#${c.id}` }}</span>
        </div>

        <!-- 2-column grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left main column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Header card -->
                <div class="relative overflow-hidden rounded-2xl border border-gray-100 shadow-sm bg-gradient-to-br from-[#1B365D] via-[#24436F] to-[#1B365D] text-white">
                    <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-[#C4A265]/20 rounded-full blur-3xl"></div>
                    <div class="relative p-6 md:p-8">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-white/60">{{ isRtl ? 'رقم الاستشارة' : 'Consultation #' }}</p>
                                <h1 class="text-2xl md:text-3xl font-bold text-[#C4A265] mt-1">
                                    {{ c.consultation_number || `#${c.id}` }}
                                </h1>
                                <div class="h-0.5 w-16 bg-gradient-to-r from-[#C4A265] to-transparent mt-2"></div>
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 mt-4 px-3 py-1 rounded-full text-xs font-semibold',
                                        statusStyle(c.status).bg,
                                        statusStyle(c.status).text,
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            statusStyle(c.status).dot,
                                            statusStyle(c.status).pulse ? 'animate-pulse' : '',
                                        ]"
                                    ></span>
                                    {{ statusLabel(c.status) }}
                                </span>
                            </div>
                            <div class="text-start md:text-end">
                                <p class="text-xs uppercase tracking-wider text-white/60">{{ isRtl ? 'الرسوم' : 'Fee' }}</p>
                                <p class="text-3xl md:text-4xl font-bold text-white tabular-nums mt-1">
                                    {{ formatMoney(c.fee) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/15 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-[#1B365D]">{{ isRtl ? 'الخط الزمني' : 'Timeline' }}</h3>
                    </div>
                    <ul class="space-y-3">
                        <li
                            v-for="(t, i) in timelineItems"
                            :key="i"
                            class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0"
                        >
                            <span class="text-sm text-gray-500">{{ isRtl ? t.labelAr : t.labelEn }}</span>
                            <span class="text-sm font-medium text-[#1B365D] tabular-nums">{{ t.value }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Medical notes -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/15 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-[#1B365D]">{{ isRtl ? 'الملاحظات الطبية' : 'Medical Notes' }}</h3>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-400 mb-1">{{ isRtl ? 'الشكوى الرئيسية' : 'Chief Complaint' }}</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ c.chief_complaint || c.patient_complaint || '—' }}</p>
                        </div>
                        <div class="h-px bg-gray-100"></div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-400 mb-1">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ c.diagnosis || visit?.diagnosis || '—' }}</p>
                        </div>
                        <div class="h-px bg-gray-100"></div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-400 mb-1">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ c.doctor_notes || visit?.doctor_notes || '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Linked visit -->
                <div
                    v-if="c.visit_id && visit"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#1B365D]/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-[#1B365D]">{{ isRtl ? 'زيارة مرتبطة' : 'Linked Visit' }}</p>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'تم تحويل الاستشارة لزيارة في العيادة' : 'Consultation converted to a clinic visit' }}</p>
                            </div>
                        </div>
                        <Link
                            :href="`/admin/visits/${c.visit_id}`"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-[#1B365D] bg-[#C4A265]/15 hover:bg-[#C4A265]/25 border border-[#C4A265]/30 transition"
                        >
                            {{ isRtl ? 'عرض الزيارة' : 'View Visit' }}
                            <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </Link>
                    </div>
                </div>

                <!-- Cancellation info -->
                <div
                    v-if="c.status === 'cancelled' && (c.cancellation_reason || c.cancelled_at || c.cancelled_by)"
                    class="bg-red-50/60 rounded-2xl border border-red-100 shadow-sm p-6"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-red-800">{{ isRtl ? 'معلومات الإلغاء' : 'Cancellation' }}</h3>
                    </div>
                    <div class="space-y-2 text-sm">
                        <p><span class="text-red-600">{{ isRtl ? 'السبب:' : 'Reason:' }}</span> <span class="text-red-900 font-medium">{{ c.cancellation_reason || '—' }}</span></p>
                        <p><span class="text-red-600">{{ isRtl ? 'بواسطة:' : 'By:' }}</span> <span class="text-red-900 font-medium">{{ c.cancelled_by || '—' }}</span></p>
                        <p><span class="text-red-600">{{ isRtl ? 'التاريخ:' : 'When:' }}</span> <span class="text-red-900 font-medium tabular-nums">{{ formatDateTime(c.cancelled_at) }}</span></p>
                    </div>
                </div>
            </div>

            <!-- Right sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Patient -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
                    <p class="text-xs uppercase tracking-wider text-gray-400 mb-3">{{ isRtl ? 'المريض' : 'Patient' }}</p>
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-full bg-[#1B365D] text-[#C4A265] flex items-center justify-center font-bold text-lg">
                            {{ initials(patient.full_name) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-[#1B365D] truncate">{{ patient.full_name || '-' }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ patient.phone || '' }}</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ isRtl ? 'رقم الملف' : 'File #' }}</span>
                            <span class="font-medium text-[#1B365D] tabular-nums">{{ patient.file_number || '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ isRtl ? 'تاريخ الميلاد' : 'DOB' }}</span>
                            <span class="font-medium text-[#1B365D] tabular-nums">{{ formatDate(patient.date_of_birth) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ isRtl ? 'النوع' : 'Gender' }}</span>
                            <span class="font-medium text-[#1B365D]">{{ genderLabel(patient.gender) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Doctor -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
                    <p class="text-xs uppercase tracking-wider text-gray-400 mb-3">{{ isRtl ? 'الطبيب' : 'Doctor' }}</p>
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-full p-[2px] bg-gradient-to-br from-[#C4A265] to-[#E6C88A]">
                            <div class="w-full h-full rounded-full bg-white p-[2px]">
                                <img
                                    v-if="photoUrl(doctor)"
                                    :src="photoUrl(doctor)"
                                    :alt="doctorName(doctor)"
                                    class="w-full h-full rounded-full object-cover"
                                />
                                <div v-else class="w-full h-full rounded-full bg-[#1B365D] text-[#C4A265] flex items-center justify-center font-bold">
                                    {{ initials(doctorName(doctor)) }}
                                </div>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-[#1B365D] truncate">{{ doctorName(doctor) }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ doctorSpecialty(doctor) }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span
                            v-if="doctor.module"
                            class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#1B365D]/10 text-[#1B365D]"
                        >
                            {{ doctor.module }}
                        </span>
                    </div>
                </div>

                <!-- Payment -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs uppercase tracking-wider text-gray-400">{{ isRtl ? 'الدفع' : 'Payment' }}</p>
                        <span
                            :class="[
                                'inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold',
                                paymentStyle(c.payment_status).bg,
                                paymentStyle(c.payment_status).text,
                            ]"
                        >
                            {{ paymentLabel(c.payment_status) }}
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ isRtl ? 'الرسوم' : 'Fee' }}</span>
                            <span class="font-bold text-[#1B365D] tabular-nums">{{ formatMoney(c.fee) }}</span>
                        </div>
                        <div v-if="c.gateway_reference" class="flex justify-between gap-2">
                            <span class="text-gray-500 whitespace-nowrap">{{ isRtl ? 'مرجع البوابة' : 'Gateway Ref' }}</span>
                            <span class="font-mono text-xs text-[#1B365D] truncate">{{ c.gateway_reference }}</span>
                        </div>
                    </div>

                    <div v-if="transactions.length" class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-[11px] uppercase tracking-wider text-gray-400 mb-2">{{ isRtl ? 'المعاملات' : 'Transactions' }}</p>
                        <ul class="space-y-2">
                            <li
                                v-for="tx in transactions"
                                :key="tx.id"
                                class="rounded-lg bg-gray-50 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-[#1B365D]">{{ tx.gateway || '—' }}</span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-semibold',
                                            paymentStyle(tx.status).bg,
                                            paymentStyle(tx.status).text,
                                        ]"
                                    >
                                        {{ paymentLabel(tx.status) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-1.5 text-gray-500 tabular-nums">
                                    <span>{{ formatDateTime(tx.created_at) }}</span>
                                    <span class="font-bold text-[#1B365D]">{{ formatMoney(tx.amount) }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Agora -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
                    <p class="text-xs uppercase tracking-wider text-gray-400 mb-3">{{ isRtl ? 'جلسة Agora' : 'Agora Session' }}</p>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-[11px] text-gray-500 mb-1">{{ isRtl ? 'اسم القناة' : 'Channel' }}</p>
                            <p class="font-mono text-xs px-3 py-2 rounded-lg bg-gray-50 text-[#1B365D] break-all">
                                {{ c.agora_channel_name || c.channel_name || '—' }}
                            </p>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ isRtl ? 'انتهاء الرمز' : 'Token expiry' }}</span>
                            <span class="font-medium text-[#1B365D] tabular-nums">{{ formatDateTime(c.agora_token_expires_at || c.token_expires_at) }}</span>
                        </div>
                        <div v-if="c.recording_url" class="pt-2 border-t border-gray-100">
                            <a
                                :href="c.recording_url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#C4A265] hover:text-[#8A6F3A]"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                                {{ isRtl ? 'مشاهدة التسجيل' : 'View recording' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
