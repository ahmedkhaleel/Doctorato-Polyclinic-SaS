<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

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
    active: 'bg-emerald-100 text-emerald-700',
    completed: 'bg-slate-100 text-[#1B365D]',
    inactive: 'bg-gray-100 text-gray-500',
};

function progressPercent(plan) {
    if (!plan.steps?.length) return 0;
    const completed = plan.steps.filter(s => s.status === 'completed').length;
    return Math.round((completed / plan.steps.length) * 100);
}
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ t('p_my_treatment_plans') }}</h1>

        <!-- Plan Cards -->
        <div v-if="plans?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link
                v-for="plan in plans.data"
                :key="plan.id"
                :href="lp('/treatment-plans/' + plan.id)"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-gray-200 transition-all duration-200 block"
            >
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-800 flex-1 min-w-0 truncate ltr:pr-3 rtl:pl-3">{{ plan.title }}</h3>
                    <span :class="statusColors[plan.status] || 'bg-gray-100 text-gray-500'" class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full flex-shrink-0">{{ plan.status }}</span>
                </div>

                <p class="text-xs text-gray-500 mb-3">{{ $localized(plan.doctor, 'name') || plan.doctor_name }}</p>

                <!-- Progress bar -->
                <div class="mb-3">
                    <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                        <span>{{ isRtl ? 'التقدم' : 'Progress' }}</span>
                        <span>{{ progressPercent(plan) }}%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="plan.status === 'completed' ? 'bg-[#1B365D]' : 'bg-[var(--brand-primary)]'"
                            :style="{ width: progressPercent(plan) + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <span>{{ plan.start_date || '—' }}</span>
                    <span v-if="plan.end_date">→ {{ plan.end_date }}</span>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد خطط علاج' : 'No treatment plans found' }}</p>
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
