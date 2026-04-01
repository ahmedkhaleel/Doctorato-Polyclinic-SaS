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
    treatments: Object,
    treatmentTypes: Object,
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
    planned: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};

const statusLabels = {
    planned: { ar: 'مخطط', en: 'Planned' },
    in_progress: { ar: 'قيد التنفيذ', en: 'In Progress' },
    completed: { ar: 'مكتمل', en: 'Completed' },
    cancelled: { ar: 'ملغي', en: 'Cancelled' },
};

function statusLabel(status) {
    const labels = statusLabels[status];
    if (!labels) return status;
    return isRtl.value ? labels.ar : labels.en;
}

function treatmentTypeLabel(type) {
    if (!props.treatmentTypes) return type;
    const typeObj = props.treatmentTypes[type];
    if (!typeObj) return type;
    return isRtl.value ? (typeObj.name_ar || typeObj.name || type) : (typeObj.name || type);
}

/* Stats */
const stats = computed(() => {
    const data = props.treatments?.data || [];
    const total = props.treatments?.total || data.length;
    const completed = data.filter(t => t.status === 'completed').length;
    const inProgress = data.filter(t => t.status === 'in_progress').length;
    return { total, completed, inProgress };
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ t('p_dental_treatments') }}</h1>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'إجمالي العلاجات' : 'Total Treatments' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ stats.completed }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'مكتملة' : 'Completed' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-yellow-600">{{ stats.inProgress }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'قيد التنفيذ' : 'In Progress' }}</p>
            </div>
        </div>

        <!-- Treatments List -->
        <div v-if="treatments?.data?.length" class="space-y-3">
            <div
                v-for="treatment in treatments.data"
                :key="treatment.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-gray-200 transition-all"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-semibold text-gray-800">{{ treatmentTypeLabel(treatment.treatment_type) }}</span>
                            <span v-if="treatment.tooth_number" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-mono">
                                #{{ treatment.tooth_number }}
                            </span>
                        </div>
                        <p v-if="treatment.description" class="text-xs text-gray-500 line-clamp-2">{{ treatment.description }}</p>
                    </div>
                    <span :class="statusColors[treatment.status] || 'bg-gray-100 text-gray-500'" class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full flex-shrink-0">
                        {{ statusLabel(treatment.status) }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-400">
                    <!-- Doctor -->
                    <div v-if="treatment.doctor" class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <span>{{ $localized(treatment.doctor, 'name') }}</span>
                    </div>
                    <!-- Visit date -->
                    <div v-if="treatment.visit?.visit_date" class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span>{{ treatment.visit.visit_date }}</span>
                    </div>
                    <!-- Surfaces -->
                    <div v-if="treatment.surfaces?.length" class="flex items-center gap-1.5">
                        <span class="text-gray-500">{{ isRtl ? 'الأسطح:' : 'Surfaces:' }}</span>
                        <span>{{ treatment.surfaces.join(', ') }}</span>
                    </div>
                    <!-- Cost -->
                    <div v-if="treatment.cost" class="flex items-center gap-1.5">
                        <span class="font-semibold text-[var(--brand-primary)]">{{ formatCurrency(treatment.cost) }}</span>
                    </div>
                </div>

                <!-- Notes -->
                <p v-if="treatment.notes" class="mt-2 text-xs text-gray-400 bg-gray-50 rounded-lg px-3 py-2">{{ treatment.notes }}</p>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد علاجات أسنان' : 'No dental treatments found' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="treatments?.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in treatments.links" :key="link.label">
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
