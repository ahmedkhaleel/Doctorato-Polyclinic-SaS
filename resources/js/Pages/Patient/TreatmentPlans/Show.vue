<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

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
    active: 'bg-green-100 text-green-700',
    completed: 'bg-blue-100 text-blue-700',
    inactive: 'bg-gray-100 text-gray-500',
};

const stepStatusColors = {
    pending: 'border-gray-300 bg-white text-gray-400',
    in_progress: 'border-[var(--brand-primary)] bg-[var(--brand-primary)]/10 text-[var(--brand-primary)]',
    completed: 'border-green-500 bg-green-500 text-white',
    skipped: 'border-gray-300 bg-gray-100 text-gray-400',
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('/treatment-plans')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'تفاصيل خطة العلاج' : 'Treatment Plan Details' }}</h1>
        </div>

        <!-- Plan Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">{{ plan?.title }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $localized(plan?.doctor, 'name') || plan?.doctor_name }}</p>
                </div>
                <span :class="statusColors[plan?.status] || 'bg-gray-100 text-gray-500'" class="text-xs font-semibold px-3 py-1 rounded-full self-start">{{ plan?.status }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-xs text-gray-400">{{ isRtl ? 'تاريخ البداية' : 'Start Date' }}</span>
                    <p class="text-gray-800 font-medium">{{ plan?.start_date || '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400">{{ isRtl ? 'تاريخ النهاية' : 'End Date' }}</span>
                    <p class="text-gray-800 font-medium">{{ plan?.end_date || '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Goals -->
        <div v-if="plan?.goals" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">{{ isRtl ? 'الأهداف' : 'Goals' }}</h3>
            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ plan.goals }}</p>
        </div>

        <!-- Description -->
        <div v-if="plan?.description" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">{{ isRtl ? 'الوصف' : 'Description' }}</h3>
            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ plan.description }}</p>
        </div>

        <!-- Steps Timeline -->
        <div v-if="plan?.steps?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6">{{ isRtl ? 'خطوات العلاج' : 'Treatment Steps' }}</h3>

            <div class="relative">
                <!-- Timeline Line -->
                <div class="absolute ltr:left-[18px] rtl:right-[18px] top-0 bottom-0 w-0.5 bg-gray-200"></div>

                <div class="space-y-6">
                    <div v-for="(step, index) in plan.steps" :key="step.id || index" class="relative flex gap-4">
                        <!-- Step indicator -->
                        <div
                            class="relative z-10 w-9 h-9 rounded-full border-2 flex items-center justify-center flex-shrink-0 text-sm font-bold transition-all"
                            :class="stepStatusColors[step.status] || stepStatusColors.pending"
                        >
                            <svg v-if="step.status === 'completed'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            <span v-else>{{ step.step_number || index + 1 }}</span>
                        </div>

                        <!-- Step content -->
                        <div class="flex-1 pb-2">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-sm font-semibold text-gray-800">{{ step.title }}</h4>
                                <span
                                    v-if="step.status"
                                    class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-gray-100 text-gray-500': step.status === 'pending',
                                        'bg-yellow-100 text-yellow-700': step.status === 'in_progress',
                                        'bg-green-100 text-green-700': step.status === 'completed',
                                        'bg-gray-100 text-gray-400': step.status === 'skipped',
                                    }"
                                >{{ step.status }}</span>
                            </div>
                            <p v-if="step.description" class="text-xs text-gray-500 leading-relaxed">{{ step.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
