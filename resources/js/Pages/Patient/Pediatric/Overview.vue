<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    visitSummary: Object,
    vaccinationSummary: Object,
    latestGrowth: Object,
    allergies: Array,
    chronicConditions: Array,
    upcomingVaccinations: Array,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ isRtl ? 'ملف طب الأطفال' : 'Pediatric Profile' }}</h1>
                <p class="text-sm text-gray-500">{{ isRtl ? 'نظرة عامة على صحة طفلك' : 'Overview of your child\'s health' }}</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ visitSummary?.total ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600">{{ vaccinationSummary?.given ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ isRtl ? 'تطعيمات مكتملة' : 'Vaccinations Given' }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-amber-600">{{ vaccinationSummary?.scheduled ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ isRtl ? 'تطعيمات مجدولة' : 'Scheduled' }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-gray-800">{{ latestGrowth ? `${latestGrowth.weight_kg} kg` : '-' }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ isRtl ? 'آخر وزن' : 'Latest Weight' }}</p>
            </div>
        </div>

        <!-- PDF Downloads -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="/patient/pediatric/vaccination-card" target="_blank" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition-shadow group flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'بطاقة التطعيمات' : 'Vaccination Card' }}</p>
                    <p class="text-xs text-gray-400">{{ isRtl ? 'تحميل PDF' : 'Download PDF' }}</p>
                </div>
            </a>
            <a href="/patient/pediatric/growth-report" target="_blank" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition-shadow group flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition-colors">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'تقرير النمو' : 'Growth Report' }}</p>
                    <p class="text-xs text-gray-400">{{ isRtl ? 'تحميل PDF' : 'Download PDF' }}</p>
                </div>
            </a>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Link href="/patient/pediatric/vaccinations" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition-shadow group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center group-hover:bg-green-100 transition-colors">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'سجل التطعيمات' : 'Vaccination Record' }}</p>
                        <p class="text-xs text-gray-400">{{ isRtl ? 'عرض جميع التطعيمات' : 'View all vaccinations' }}</p>
                    </div>
                </div>
            </Link>
            <Link href="/patient/pediatric/growth" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition-shadow group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'منحنى النمو' : 'Growth Chart' }}</p>
                        <p class="text-xs text-gray-400">{{ isRtl ? 'الوزن والطول' : 'Weight & Height' }}</p>
                    </div>
                </div>
            </Link>
            <Link href="/patient/pediatric/visits" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition-shadow group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition-colors">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'سجل الزيارات' : 'Visit History' }}</p>
                        <p class="text-xs text-gray-400">{{ isRtl ? 'عرض الزيارات السابقة' : 'View past visits' }}</p>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Upcoming Vaccinations -->
        <div v-if="upcomingVaccinations?.length" class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'التطعيمات القادمة' : 'Upcoming Vaccinations' }}</h2>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="v in upcomingVaccinations" :key="v.id" class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ isRtl ? v.vaccine_name_ar : v.vaccine_name }}</p>
                        <p class="text-xs text-gray-400">{{ v.dose_number }} &middot; {{ v.scheduled_age }}</p>
                    </div>
                    <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">{{ formatDate(v.scheduled_date) }}</span>
                </div>
            </div>
        </div>

        <!-- Allergies & Conditions -->
        <div v-if="allergies?.length || chronicConditions?.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="allergies?.length" class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-red-600">{{ isRtl ? 'الحساسيات النشطة' : 'Active Allergies' }}</h2>
                </div>
                <div class="p-5 space-y-2">
                    <div v-for="a in allergies" :key="a.id" class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" :class="a.severity === 'severe' ? 'bg-red-500' : a.severity === 'moderate' ? 'bg-amber-500' : 'bg-green-500'"></span>
                        <span class="text-sm text-gray-700">{{ isRtl ? a.allergen_ar : a.allergen }}</span>
                    </div>
                </div>
            </div>
            <div v-if="chronicConditions?.length" class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-blue-600">{{ isRtl ? 'الأمراض المزمنة' : 'Chronic Conditions' }}</h2>
                </div>
                <div class="p-5 space-y-2">
                    <div v-for="c in chronicConditions" :key="c.id" class="text-sm text-gray-700">
                        {{ isRtl ? c.condition_ar : c.condition }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
