<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const { t } = useLocale();
const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    treatments: Object,
    filters: Object,
    treatmentTypes: Array,
});

// ─── Toast Notification ─────────────────────────────────
const showSuccess = ref(false);
const successMessage = ref('');
watch(() => page.props.flash?.success, (msg) => {
    if (msg) { successMessage.value = msg; showSuccess.value = true; setTimeout(() => { showSuccess.value = false; }, 4000); }
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const typeFilter = ref(props.filters?.treatment_type || '');
const toothNumber = ref(props.filters?.tooth_number || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const isFiltering = ref(false);

let searchTimeout = null;

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        isFiltering.value = true;
        router.get('/doctor/dental/treatments', {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            treatment_type: typeFilter.value || undefined,
            tooth_number: toothNumber.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => { isFiltering.value = false; },
        });
    }, 400);
}

watch([search, statusFilter, typeFilter, toothNumber, dateFrom, dateTo], applyFilters);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusColors = {
    planned: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-[#C4A265]/10 text-[#C4A265]',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-red-100 text-red-700',
};

const statusLabels = computed(() => ({
    planned: isRtl.value ? 'مخطط' : 'Planned',
    in_progress: isRtl.value ? 'قيد التنفيذ' : 'In Progress',
    completed: isRtl.value ? 'مكتمل' : 'Completed',
    cancelled: isRtl.value ? 'ملغي' : 'Cancelled',
}));

// Quick status actions for each treatment
const nextStatusMap = {
    planned: 'in_progress',
    in_progress: 'completed',
};
const nextStatusLabel = computed(() => ({
    planned: isRtl.value ? 'بدء العلاج' : 'Start',
    in_progress: isRtl.value ? 'إكمال' : 'Complete',
}));
const nextStatusIcon = {
    planned: '▶',
    in_progress: '✓',
};

function advanceTreatmentStatus(treatmentId, currentStatus) {
    const next = nextStatusMap[currentStatus];
    if (!next) return;
    router.post(`/doctor/dental/treatments/${treatmentId}`, { status: next }, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-8">
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-[#1B365D]/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'طب الأسنان' : 'Dental' }}</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'العلاجات' : 'Treatments' }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'جميع علاجات الأسنان الخاصة بك' : 'All your dental treatments' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 text-center">
                            <p class="text-2xl font-bold text-white">{{ treatments.total || 0 }}</p>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي العلاجات' : 'Total Treatments' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 max-w-3xl dental-card-enter" style="animation-delay:0.15s">
                    <div class="relative flex-1">
                        <svg v-if="!isFiltering" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <svg v-else class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#C4A265] animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالمريض، رقم السن، الملاحظات...' : 'Search patient, tooth #, notes...'"
                            class="doctorato-input w-full pl-12 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="doctorato-input px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition-all"
                    >
                        <option value="" class="text-gray-900">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                        <option value="planned" class="text-gray-900">{{ isRtl ? 'مخطط' : 'Planned' }}</option>
                        <option value="in_progress" class="text-gray-900">{{ isRtl ? 'قيد التنفيذ' : 'In Progress' }}</option>
                        <option value="completed" class="text-gray-900">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                        <option value="cancelled" class="text-gray-900">{{ isRtl ? 'ملغي' : 'Cancelled' }}</option>
                    </select>
                    <select
                        v-model="typeFilter"
                        class="doctorato-input px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition-all"
                    >
                        <option value="" class="text-gray-900">{{ isRtl ? 'جميع الأنواع' : 'All Types' }}</option>
                        <option v-for="tt in treatmentTypes" :key="tt.id" :value="tt.id" class="text-gray-900">
                            {{ locale === 'ar' ? (tt.name_ar || tt.name_en) : (tt.name_en || tt.name_ar) }}
                        </option>
                    </select>
                </div>
                <!-- Advanced Filters Row -->
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 max-w-3xl mt-3 dental-card-enter" style="animation-delay:0.2s">
                    <input
                        v-model="toothNumber"
                        type="text"
                        :placeholder="isRtl ? 'رقم السن (14, 36...)' : 'Tooth # (14, 36...)'"
                        class="doctorato-input px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all w-36 font-mono"
                    />
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ isRtl ? 'من' : 'From' }}</span>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="doctorato-input px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition-all"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ isRtl ? 'إلى' : 'To' }}</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="doctorato-input px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition-all"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay:0.25s">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'رقم السن' : 'Tooth #' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'نوع العلاج' : 'Treatment Type' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="treatment in treatments.data" :key="treatment.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-semibold text-gray-800">{{ treatment.patient?.full_name || '-' }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ treatment.patient?.file_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="font-mono font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded">{{ treatment.tooth_number || '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ treatment.treatment_type ? (locale === 'ar' ? (treatment.treatment_type.name_ar || treatment.treatment_type.name_en) : (treatment.treatment_type.name_en || treatment.treatment_type.name_ar)) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span :class="[statusColors[treatment.status] || 'bg-gray-100 text-gray-700', 'px-2.5 py-1 rounded-full text-xs font-medium']">
                                        {{ statusLabels[treatment.status] || treatment.status }}
                                    </span>
                                    <button v-if="nextStatusMap[treatment.status]"
                                        @click="advanceTreatmentStatus(treatment.id, treatment.status)"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-semibold rounded-lg transition-all"
                                        :class="treatment.status === 'planned'
                                            ? 'bg-[#C4A265]/10 text-[#C4A265] hover:bg-[#C4A265]/20'
                                            : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                                        :title="nextStatusLabel[treatment.status]">
                                        <span>{{ nextStatusIcon[treatment.status] }}</span>
                                        <span class="hidden sm:inline">{{ nextStatusLabel[treatment.status] }}</span>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 font-medium">{{ formatCurrency(treatment.cost) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(treatment.treatment_date || treatment.created_at) }}</td>
                        </tr>
                        <tr v-if="!treatments.data || treatments.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                </div>
                                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد علاجات' : 'No treatments found' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="treatments.links && treatments.links.length > 3" class="flex items-center justify-center gap-1 px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-wrap">
                <template v-for="link in treatments.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'"
                        v-html="link.label" preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Success Toast -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0"
        >
            <div v-if="showSuccess" class="fixed bottom-6 ltr:right-6 rtl:left-6 z-50 flex items-center gap-3 px-5 py-3 bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-200/50">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ successMessage }}</span>
            </div>
        </Transition>
    </div>
</template>

<style>
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
</style>
