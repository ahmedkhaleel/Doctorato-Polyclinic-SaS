<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    prescriptions: Object,
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
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ t('p_my_prescriptions') }}</h1>

        <!-- Prescription Cards -->
        <div v-if="prescriptions?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link
                v-for="rx in prescriptions.data"
                :key="rx.id"
                :href="lp('/prescriptions/' + rx.id)"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-gray-200 transition-all duration-200 block"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-[var(--brand-primary)]/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" /></svg>
                    </div>
                    <span class="text-xs text-gray-400">{{ rx.prescription_date || rx.created_at?.split('T')[0] }}</span>
                </div>
                <h3 class="text-sm font-semibold text-gray-800 mb-1">{{ $localized(rx.doctor, 'name') || rx.doctor_name }}</h3>
                <p v-if="rx.diagnosis" class="text-xs text-gray-500 line-clamp-2">{{ rx.diagnosis }}</p>
                <div class="mt-3 flex items-center gap-1.5 text-[var(--brand-primary)] text-xs font-medium">
                    <span>{{ isRtl ? 'عرض التفاصيل' : 'View Details' }}</span>
                    <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" /></svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد وصفات طبية' : 'No prescriptions found' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="prescriptions?.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in prescriptions.links" :key="link.label">
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
