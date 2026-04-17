<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    claim: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

function fmt(n) {
    if (!n && n !== 0) return '—'
    return Number(n).toLocaleString() + ' EGP'
}

const statusColors = {
    draft: 'bg-gray-100 text-gray-700',
    submitted: 'bg-slate-50 text-[#1B365D]',
    under_review: 'bg-amber-50 text-amber-700',
    approved: 'bg-emerald-50 text-emerald-700',
    partially_approved: 'bg-teal-50 text-teal-700',
    rejected: 'bg-red-50 text-red-700',
    paid: 'bg-emerald-100 text-emerald-800',
    partially_paid: 'bg-sky-50 text-sky-700',
}

const statusLabels = {
    draft: { ar: 'مسودة', en: 'Draft' },
    submitted: { ar: 'تم الإرسال', en: 'Submitted' },
    under_review: { ar: 'قيد المراجعة', en: 'Under Review' },
    approved: { ar: 'معتمد', en: 'Approved' },
    partially_approved: { ar: 'معتمد جزئياً', en: 'Partially Approved' },
    rejected: { ar: 'مرفوض', en: 'Rejected' },
    paid: { ar: 'مدفوع', en: 'Paid' },
    partially_paid: { ar: 'مدفوع جزئياً', en: 'Partially Paid' },
}

function statusLabel(s) {
    return statusLabels[s]?.[isRtl.value ? 'ar' : 'en'] || s
}

const timeline = computed(() => [
    { key: 'submitted', labelAr: 'تم الإرسال', labelEn: 'Submitted', date: props.claim.submitted_at, done: !!props.claim.submitted_at },
    { key: 'approved',  labelAr: 'تم الاعتماد', labelEn: 'Approved',  date: props.claim.approved_at,  done: !!props.claim.approved_at },
    { key: 'paid',      labelAr: 'تم الدفع',    labelEn: 'Paid',      date: props.claim.paid_at,      done: !!props.claim.paid_at },
])
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-3xl mb-6 p-7"
             style="background: linear-gradient(135deg, #1B365D 0%, #254677 55%, #1B365D 100%);">
            <div class="absolute inset-0 opacity-20 pointer-events-none"
                 style="background-image: radial-gradient(circle at 80% 50%, #C4A265 0%, transparent 40%);"></div>
            <div class="relative flex items-start justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl md:text-3xl font-bold" style="color: #C4A265;">
                            {{ claim.claim_number }}
                        </h1>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="statusColors[claim.status]">
                            {{ statusLabel(claim.status) }}
                        </span>
                    </div>
                    <p class="text-sm text-white/70 mt-2">
                        {{ isRtl ? 'تاريخ الخدمة' : 'Service date' }}: <span class="font-semibold text-white">{{ claim.service_date }}</span>
                    </p>
                </div>
                <Link href="/admin/insurance/claims" class="px-4 py-2 rounded-xl text-sm border border-white/30 text-white hover:bg-white/10">{{ isRtl ? 'رجوع للمطالبات' : 'Back to Claims' }}</Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main content -->
            <div class="lg:col-span-2 space-y-5">
                <!-- Diagnosis & Services -->
                <section class="bg-white rounded-2xl border border-gray-100 p-6">
                    <h2 class="text-sm font-bold text-[#1B365D] mb-4 flex items-center gap-2">
                        <span class="w-1 h-5 rounded bg-[#C4A265]"></span>
                        {{ isRtl ? 'التشخيص والخدمات' : 'Diagnosis & Services' }}
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</div>
                            <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ claim.diagnosis || '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'وصف الخدمات' : 'Services Description' }}</div>
                            <div class="text-sm text-gray-800 whitespace-pre-wrap bg-gray-50 rounded-xl p-3">{{ claim.services_description || '—' }}</div>
                        </div>
                        <div v-if="claim.notes">
                            <div class="text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</div>
                            <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ claim.notes }}</div>
                        </div>
                        <div v-if="claim.rejection_reason" class="bg-red-50 border border-red-100 rounded-xl p-4">
                            <div class="text-xs font-bold text-red-700 mb-1">{{ isRtl ? 'سبب الرفض' : 'Rejection Reason' }}</div>
                            <div class="text-sm text-red-800">{{ claim.rejection_reason }}</div>
                        </div>
                    </div>
                </section>

                <!-- Timeline -->
                <section class="bg-white rounded-2xl border border-gray-100 p-6">
                    <h2 class="text-sm font-bold text-[#1B365D] mb-5 flex items-center gap-2">
                        <span class="w-1 h-5 rounded bg-[#C4A265]"></span>
                        {{ isRtl ? 'المسار الزمني' : 'Timeline' }}
                    </h2>
                    <div class="relative">
                        <div class="absolute top-4 start-4 end-4 h-0.5 bg-gray-100"></div>
                        <div class="relative flex items-start justify-between">
                            <div v-for="(step, i) in timeline" :key="step.key" class="flex flex-col items-center text-center" style="flex: 1;">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center z-10 relative"
                                     :class="step.done ? 'bg-[#1B365D] text-white' : 'bg-gray-100 text-gray-400'">
                                    <svg v-if="step.done" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span v-else class="text-xs">{{ i + 1 }}</span>
                                </div>
                                <div class="mt-2 text-xs font-medium" :class="step.done ? 'text-[#1B365D]' : 'text-gray-400'">
                                    {{ isRtl ? step.labelAr : step.labelEn }}
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">{{ step.date || '—' }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Linked invoice/visit -->
                <section v-if="claim.invoice || claim.visit" class="bg-white rounded-2xl border border-gray-100 p-6">
                    <h2 class="text-sm font-bold text-[#1B365D] mb-4 flex items-center gap-2">
                        <span class="w-1 h-5 rounded bg-[#C4A265]"></span>
                        {{ isRtl ? 'مرتبط بـ' : 'Linked Records' }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <Link v-if="claim.invoice" :href="`/admin/invoices/${claim.invoice.id}`" class="flex items-center justify-between p-4 rounded-xl bg-gray-50 hover:bg-[#C4A265]/10 transition">
                            <div>
                                <div class="text-xs text-gray-500">{{ isRtl ? 'الفاتورة' : 'Invoice' }}</div>
                                <div class="font-semibold text-[#1B365D]">{{ claim.invoice.invoice_number }}</div>
                                <div class="text-xs text-gray-600 mt-0.5">{{ fmt(claim.invoice.total) }}</div>
                            </div>
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'"/></svg>
                        </Link>
                        <Link v-if="claim.visit" :href="`/admin/visits/${claim.visit.id}`" class="flex items-center justify-between p-4 rounded-xl bg-gray-50 hover:bg-[#C4A265]/10 transition">
                            <div>
                                <div class="text-xs text-gray-500">{{ isRtl ? 'الزيارة' : 'Visit' }}</div>
                                <div class="font-semibold text-[#1B365D]">#{{ claim.visit.id }}</div>
                            </div>
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'"/></svg>
                        </Link>
                    </div>
                </section>
            </div>

            <!-- Sidebar -->
            <div class="space-y-5">
                <!-- Patient -->
                <section class="bg-white rounded-2xl border border-gray-100 p-5">
                    <div class="text-xs font-bold text-[#1B365D] uppercase mb-3">{{ isRtl ? 'المريض' : 'Patient' }}</div>
                    <div v-if="claim.patient" class="space-y-1">
                        <div class="font-semibold text-gray-800">{{ claim.patient.full_name }}</div>
                        <div class="text-xs text-gray-500 font-mono">{{ claim.patient.file_number }}</div>
                        <div v-if="claim.patient.phone" class="text-xs text-gray-600">{{ claim.patient.phone }}</div>
                        <Link :href="`/admin/patients/${claim.patient.id}`" class="inline-block mt-2 text-xs text-[#C4A265] hover:underline">
                            {{ isRtl ? 'عرض الملف الكامل ←' : 'View full file →' }}
                        </Link>
                    </div>
                </section>

                <!-- Insurance -->
                <section class="bg-white rounded-2xl border border-gray-100 p-5">
                    <div class="text-xs font-bold text-[#1B365D] uppercase mb-3">{{ isRtl ? 'التأمين' : 'Insurance' }}</div>
                    <div v-if="claim.patient_insurance" class="space-y-2">
                        <div class="font-semibold text-gray-800">{{ isRtl ? claim.patient_insurance.company?.name_ar : claim.patient_insurance.company?.name_en }}</div>
                        <div v-if="claim.patient_insurance.plan" class="flex items-center gap-2 text-xs">
                            <span class="text-gray-600">{{ isRtl ? claim.patient_insurance.plan.name_ar : claim.patient_insurance.plan.name_en }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-[#C4A265]/15 text-[#8B6F3F] font-bold">{{ claim.patient_insurance.plan.class }}</span>
                        </div>
                        <div class="text-xs text-gray-600 font-mono">{{ claim.patient_insurance.member_id }}</div>
                        <div v-if="claim.patient_insurance.remaining_balance !== null" class="pt-2 border-t border-gray-100 mt-2">
                            <div class="text-xs text-gray-500">{{ isRtl ? 'الرصيد المتبقي' : 'Remaining balance' }}</div>
                            <div class="font-bold text-[#1B365D]">{{ fmt(claim.patient_insurance.remaining_balance) }}</div>
                        </div>
                    </div>
                </section>

                <!-- Financial -->
                <section class="bg-white rounded-2xl border border-[#C4A265]/30 p-5" style="background: linear-gradient(180deg, #fff 0%, #fefaf3 100%);">
                    <div class="text-xs font-bold text-[#1B365D] uppercase mb-3">{{ isRtl ? 'التفاصيل المالية' : 'Financial Breakdown' }}</div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                            <span class="font-semibold text-gray-800">{{ fmt(claim.total_amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">{{ isRtl ? 'المغطى' : 'Covered' }}</span>
                            <span class="font-semibold text-emerald-700">{{ fmt(claim.covered_amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">{{ isRtl ? 'حصة المريض' : 'Patient Share' }}</span>
                            <span class="font-semibold text-amber-700">{{ fmt(claim.patient_share) }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-[#C4A265]/20">
                            <span class="text-gray-600">{{ isRtl ? 'المعتمد' : 'Approved' }}</span>
                            <span class="font-semibold text-[#1B365D]">{{ fmt(claim.approved_amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">{{ isRtl ? 'المدفوع' : 'Paid' }}</span>
                            <span class="font-bold text-lg" style="color: #C4A265;">{{ fmt(claim.paid_amount) }}</span>
                        </div>
                    </div>
                </section>

                <!-- Meta -->
                <section class="bg-white rounded-2xl border border-gray-100 p-5 text-xs text-gray-500 space-y-1">
                    <div v-if="claim.reference_number">
                        <span class="font-medium">{{ isRtl ? 'الرقم المرجعي' : 'Reference' }}:</span>
                        <span class="font-mono text-gray-700"> {{ claim.reference_number }}</span>
                    </div>
                    <div v-if="claim.creator">
                        <span class="font-medium">{{ isRtl ? 'أنشأها' : 'Created by' }}:</span>
                        <span class="text-gray-700"> {{ claim.creator.name }}</span>
                    </div>
                    <div>
                        <span class="font-medium">{{ isRtl ? 'تاريخ الإنشاء' : 'Created' }}:</span>
                        <span class="text-gray-700"> {{ claim.created_at }}</span>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
