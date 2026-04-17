<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    vaccinations: Array,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusColors = {
    given: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    scheduled: 'bg-amber-50 text-amber-700 border-amber-200',
    missed: 'bg-red-50 text-red-700 border-red-200',
    postponed: 'bg-gray-50 text-gray-700 border-gray-200',
    contraindicated: 'bg-slate-50 text-[#1B365D] border-slate-200',
};

const statusLabels = {
    given: { ar: 'تم إعطاؤه', en: 'Given' },
    scheduled: { ar: 'مجدول', en: 'Scheduled' },
    missed: { ar: 'فائت', en: 'Missed' },
    postponed: { ar: 'مؤجل', en: 'Postponed' },
    contraindicated: { ar: 'موانع', en: 'Contraindicated' },
};
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link href="/patient/pediatric/overview" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ isRtl ? 'سجل التطعيمات' : 'Vaccination Record' }}</h1>
                <p class="text-sm text-gray-500">{{ isRtl ? 'جميع تطعيمات طفلك' : 'All your child\'s vaccinations' }}</p>
            </div>
        </div>

        <!-- Vaccination List -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="divide-y divide-gray-50">
                <div v-for="v in vaccinations" :key="v.id" class="px-5 py-4 flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800">{{ isRtl ? v.vaccine_name_ar : v.vaccine_name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ v.dose_number }} &middot; {{ v.scheduled_age }}</p>
                        <p v-if="v.doctor" class="text-xs text-gray-400 mt-0.5">{{ isRtl ? v.doctor.name_ar : v.doctor.name_en }}</p>
                    </div>
                    <div class="text-end">
                        <span class="inline-block text-[11px] font-medium px-2.5 py-1 rounded-full border" :class="statusColors[v.status] || 'bg-gray-50 text-gray-500'">
                            {{ isRtl ? statusLabels[v.status]?.ar : statusLabels[v.status]?.en }}
                        </span>
                        <p class="text-[11px] text-gray-400 mt-1">{{ formatDate(v.status === 'given' ? v.given_date : v.scheduled_date) }}</p>
                    </div>
                </div>
            </div>
            <div v-if="!vaccinations?.length" class="py-16 text-center">
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد تطعيمات مسجلة' : 'No vaccinations recorded' }}</p>
            </div>
        </div>
    </div>
</template>
