<script setup>
import { computed, ref, watch } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { usePatientStatus } from '@/Composables/usePatientStatus';

const { lp } = usePatientLocale();
const { visitLabel, visitColor } = usePatientStatus();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    visits: Object,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const modules = computed(() => page.props.modules || {});
// Only medical modules — visits are always medical, not HR/Inventory/Insurance
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false && m.is_medical !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const moduleFilter = ref(props.filters?.module || '');

watch(moduleFilter, () => applyFilters());

function applyFilters() {
    router.get(lp('/visits'), { module: moduleFilter.value || undefined }, { preserveState: true, replace: true });
}

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}

</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ t('p_my_visits') }}</h1>
            <select v-if="activeModules.length> 1" v-model="moduleFilter" class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="visit in visits?.data" :key="visit.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ visit.visit_date }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $localized(visit.doctor, 'name') }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                <div class="flex items-center gap-1.5">
                                    <svg v-if="visit.module === 'dental'" class="w-3.5 h-3.5 text-[#1B365D] inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                                    {{ $localized(visit.service, 'name') || $localized(visit, 'service_name') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 max-w-[200px] truncate">{{ visit.diagnosis || '—' }}</td>
                            <td class="px-6 py-4">
                                <span :class="visitColor(visit.status)" class="text-xs font-medium px-2.5 py-1 rounded-full">{{ visitLabel(visit.status) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <Link :href="lp('/visits/' + visit.id)" class="text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] text-sm font-medium">
                                    {{ isRtl ? 'عرض' : 'View' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-gray-100">
                <Link v-for="visit in visits?.data" :key="visit.id" :href="lp('/visits/' + visit.id)" class="block p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-800">{{ visit.visit_date }}</span>
                        <span :class="visitColor(visit.status)" class="text-xs font-medium px-2.5 py-1 rounded-full">{{ visitLabel(visit.status) }}</span>
                    </div>
                    <p class="text-sm text-gray-600">{{ $localized(visit.doctor, 'name') }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <svg v-if="visit.module === 'dental'" class="w-3 h-3 text-[#1B365D] inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg> {{ $localized(visit.service, 'name') || $localized(visit, 'service_name') }}
                    </p>
                    <p v-if="visit.diagnosis" class="text-xs text-gray-500 mt-1 truncate">{{ visit.diagnosis }}</p>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="!visits?.data?.length" class="text-center py-16 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                <p class="text-sm">{{ isRtl ? 'لا توجد زيارات' : 'No visits found' }}</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="visits?.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in visits.links" :key="link.label">
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
