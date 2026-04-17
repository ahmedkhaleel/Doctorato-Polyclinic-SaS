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
    plans: Object,
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
    approved: 'bg-slate-100 text-[#1B365D]',
    in_progress: 'bg-yellow-100 text-amber-700',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-red-100 text-red-700',
};

const statusLabels = {
    draft: { ar: 'مسودة', en: 'Draft' },
    approved: { ar: 'معتمدة', en: 'Approved' },
    in_progress: { ar: 'قيد التنفيذ', en: 'In Progress' },
    completed: { ar: 'مكتملة', en: 'Completed' },
    cancelled: { ar: 'ملغية', en: 'Cancelled' },
};

const priorityColors = {
    low: 'text-emerald-500',
    medium: 'text-amber-500',
    high: 'text-amber-500',
    urgent: 'text-red-500',
};

function statusLabel(status) {
    const labels = statusLabels[status];
    if (!labels) return status;
    return isRtl.value ? labels.ar : labels.en;
}

function progressPercent(plan) {
    if (!plan.estimated_sessions || plan.estimated_sessions === 0) return 0;
    return Math.min(100, Math.round((plan.completed_sessions / plan.estimated_sessions) * 100));
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ t('p_dental_plans') }}</h1>
        </div>

        <!-- Plan Cards -->
        <div v-if="plans?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link
                v-for="plan in plans.data"
                :key="plan.id"
                :href="lp('/dental/treatment-plans/' + plan.id)"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-gray-200 transition-all duration-200 block"
            >
                <!-- Title & Status -->
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-800 flex-1 min-w-0 truncate ltr:pr-3 rtl:pl-3">
                        {{ isRtl ? (plan.title_ar || plan.title_en) : (plan.title_en || plan.title_ar) }}
                    </h3>
                    <span :class="statusColors[plan.status] || 'bg-gray-100 text-gray-500'" class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full flex-shrink-0">
                        {{ statusLabel(plan.status) }}
                    </span>
                </div>

                <!-- Doctor -->
                <p v-if="plan.doctor" class="text-xs text-gray-500 mb-3">{{ $localized(plan.doctor, 'name') }}</p>

                <!-- Progress bar -->
                <div class="mb-3">
                    <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                        <span>{{ isRtl ? 'التقدم' : 'Progress' }}</span>
                        <span>{{ plan.completed_sessions || 0 }} / {{ plan.estimated_sessions || '—' }} {{ isRtl ? 'جلسة' : 'sessions' }}</span>
                    </div>
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="plan.status === 'completed' ? 'bg-emerald-500' : 'bg-[var(--brand-primary)]'"
                            :style="{ width: progressPercent(plan) + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Meta -->
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span>{{ plan.start_date || '—' }}</span>
                    </div>
                    <div v-if="plan.estimated_cost" class="font-semibold text-[var(--brand-primary)]">
                        {{ formatCurrency(plan.estimated_cost) }}
                    </div>
                </div>

                <!-- Consent Badge -->
                <div v-if="plan.consent && plan.consent.status === 'pending'" class="mt-2 flex items-center gap-1.5">
                    <span class="flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold text-[#1B365D] bg-slate-50 border border-slate-200 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        {{ isRtl ? 'بحاجة لتوقيعك' : 'Needs your signature' }}
                    </span>
                </div>

                <!-- Priority & Treatments Count -->
                <div class="flex items-center justify-between mt-2 text-xs">
                    <span v-if="plan.priority" :class="priorityColors[plan.priority]" class="font-medium capitalize">
                        {{ plan.priority }}
                    </span>
                    <span v-if="plan.treatments_count" class="text-gray-400">
                        {{ plan.treatments_count }} {{ isRtl ? 'علاج' : 'treatments' }}
                    </span>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد خطط علاج أسنان' : 'No dental treatment plans found' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="plans?.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in plans.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="link.active ? 'bg-[var(--brand-primary)] text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    v-html="link.label"
                />
                <span v-else class="px-3 py-2 text-sm text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
