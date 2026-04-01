<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    plan: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}

const statusColors = {
    draft: 'bg-gray-100 text-gray-600',
    approved: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    planned: 'bg-blue-100 text-blue-700',
};

const statusLabels = {
    draft: { ar: 'مسودة', en: 'Draft' },
    approved: { ar: 'معتمدة', en: 'Approved' },
    in_progress: { ar: 'قيد التنفيذ', en: 'In Progress' },
    completed: { ar: 'مكتمل', en: 'Completed' },
    cancelled: { ar: 'ملغي', en: 'Cancelled' },
    planned: { ar: 'مخطط', en: 'Planned' },
};

function statusLabel(status) {
    const labels = statusLabels[status];
    if (!labels) return status;
    return isRtl.value ? labels.ar : labels.en;
}

function progressPercent() {
    if (!props.plan?.estimated_sessions || props.plan.estimated_sessions === 0) return 0;
    return Math.min(100, Math.round((props.plan.completed_sessions / props.plan.estimated_sessions) * 100));
}

const planTitle = computed(() => {
    if (!props.plan) return '';
    return isRtl.value ? (props.plan.title_ar || props.plan.title_en) : (props.plan.title_en || props.plan.title_ar);
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('/dental/treatment-plans')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ planTitle }}</h1>
                <p class="text-sm text-gray-400">{{ t('p_dental_plans') }}</p>
            </div>
        </div>

        <!-- Plan Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">{{ planTitle }}</h2>
                    <p v-if="plan.doctor" class="text-sm text-gray-500 mt-1">
                        {{ $localized(plan.doctor, 'name') }}
                        <span v-if="$localized(plan.doctor, 'specialization')" class="text-gray-400">
                            — {{ $localized(plan.doctor, 'specialization') }}
                        </span>
                    </p>
                </div>
                <span :class="statusColors[plan.status] || 'bg-gray-100 text-gray-500'" class="text-xs font-semibold px-3 py-1 rounded-full">
                    {{ statusLabel(plan.status) }}
                </span>
            </div>

            <!-- Consent Banner -->
            <div v-if="plan.consent && plan.consent.status === 'pending'" class="mb-4 p-4 bg-gradient-to-r from-cyan-50 to-teal-50 border border-cyan-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-cyan-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-cyan-800">
                            {{ isRtl ? 'مطلوب توقيعك على خطة العلاج' : 'Your signature is required' }}
                        </p>
                        <p class="text-xs text-cyan-600">
                            {{ isRtl ? 'يرجى مراجعة الخطة والتوقيع للموافقة على بدء العلاج' : 'Please review and sign to consent to treatment' }}
                        </p>
                    </div>
                    <Link :href="lp('/dental/consent/' + plan.consent.id)"
                        class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-cyan-500 to-teal-500 rounded-xl hover:from-cyan-600 hover:to-teal-600 transition-all shadow-md shadow-cyan-200/50 flex-shrink-0">
                        {{ isRtl ? 'وقّع الآن' : 'Sign Now' }}
                    </Link>
                </div>
            </div>

            <div v-else-if="plan.consent && plan.consent.status === 'signed'" class="mb-4 p-3 bg-green-50 border border-green-100 rounded-xl flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-sm text-green-700 font-medium">
                    {{ isRtl ? 'تم التوقيع على الموافقة' : 'Consent signed' }}
                </span>
            </div>

            <!-- Description -->
            <p v-if="plan.description" class="text-sm text-gray-600 mb-4 leading-relaxed">{{ plan.description }}</p>

            <!-- Progress -->
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                    <span>{{ isRtl ? 'التقدم' : 'Progress' }}</span>
                    <span class="font-semibold">{{ progressPercent() }}%</span>
                </div>
                <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-700 ease-out"
                        :class="plan.status === 'completed' ? 'bg-green-500' : 'bg-[var(--brand-primary)]'"
                        :style="{ width: progressPercent() + '%' }"
                    ></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    {{ plan.completed_sessions || 0 }} / {{ plan.estimated_sessions || '—' }} {{ isRtl ? 'جلسة مكتملة' : 'sessions completed' }}
                </p>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-gray-100">
                <div>
                    <p class="text-xs text-gray-400">{{ isRtl ? 'التكلفة التقديرية' : 'Estimated Cost' }}</p>
                    <p class="text-sm font-semibold text-gray-700 mt-0.5">
                        {{ plan.estimated_cost ? formatCurrency(plan.estimated_cost) : '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">{{ isRtl ? 'التكلفة الفعلية' : 'Actual Cost' }}</p>
                    <p class="text-sm font-semibold text-gray-700 mt-0.5">
                        {{ plan.actual_cost ? formatCurrency(plan.actual_cost) : '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">{{ isRtl ? 'تاريخ البدء' : 'Start Date' }}</p>
                    <p class="text-sm font-semibold text-gray-700 mt-0.5">{{ plan.start_date || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">{{ isRtl ? 'تاريخ الانتهاء المتوقع' : 'Expected End' }}</p>
                    <p class="text-sm font-semibold text-gray-700 mt-0.5">{{ plan.expected_end_date || '—' }}</p>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="plan.notes" class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-400 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</p>
                <p class="text-sm text-gray-600 leading-relaxed">{{ plan.notes }}</p>
            </div>
        </div>

        <!-- Treatments Timeline -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'العلاجات المضمنة' : 'Included Treatments' }}</h3>
            </div>

            <div v-if="plan.treatments?.length" class="divide-y divide-gray-50">
                <div
                    v-for="(treatment, index) in plan.treatments"
                    :key="treatment.id"
                    class="px-6 py-4 hover:bg-gray-50/50 transition-colors"
                >
                    <div class="flex items-start gap-4">
                        <!-- Step Number -->
                        <div
                            :class="treatment.status === 'completed' ? 'bg-green-500 text-white' : treatment.status === 'in_progress' ? 'bg-[var(--brand-primary)] text-white' : 'bg-gray-100 text-gray-400'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                        >
                            <svg v-if="treatment.status === 'completed'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span v-else>{{ index + 1 }}</span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-gray-800">{{ treatment.treatment_type }}</span>
                                <span v-if="treatment.tooth_number" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-mono">
                                    #{{ treatment.tooth_number }}
                                </span>
                                <span :class="statusColors[treatment.status] || 'bg-gray-100 text-gray-500'" class="text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    {{ statusLabel(treatment.status) }}
                                </span>
                            </div>
                            <p v-if="treatment.description" class="text-xs text-gray-500 mb-1">{{ treatment.description }}</p>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-400 mt-1">
                                <span v-if="treatment.doctor" class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ $localized(treatment.doctor, 'name') }}
                                </span>
                                <span v-if="treatment.cost" class="font-semibold text-[var(--brand-primary)]">
                                    {{ formatCurrency(treatment.cost) }}
                                </span>
                                <span v-if="treatment.completed_at">
                                    {{ treatment.completed_at }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="p-8 text-center">
                <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد علاجات مضافة بعد' : 'No treatments added yet' }}</p>
            </div>
        </div>
    </div>
</template>
