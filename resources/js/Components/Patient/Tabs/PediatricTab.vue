<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PediatricGrowthChart from '@/Components/PediatricGrowthChart.vue';

const props = defineProps({
    data: { type: Object, default: null },
    patient: { type: Object, required: true },
    role: { type: String, default: 'admin' },
    readonly: { type: Boolean, default: false },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const stats = computed(() => props.data?.stats || {});
const growthRecords = computed(() => props.data?.growthRecords || []);
const vaccinations = computed(() => props.data?.vaccinations || []);
const allergies = computed(() => props.data?.allergies || []);
const chronicConditions = computed(() => props.data?.chronicConditions || []);
const milestones = computed(() => props.data?.milestones || []);
const screeningTests = computed(() => props.data?.screeningTests || []);

const vaccinationsLink = computed(() => {
    if (props.role === 'admin') return `/admin/pediatric/patients/${props.patient.id}/vaccinations`;
    if (props.role === 'doctor') return `/doctor/pediatric/vaccinations?patient_id=${props.patient.id}`;
    if (props.role === 'secretary') return `/secretary/pediatric/patients/${props.patient.id}/vaccinations`;
    return null;
});

const growthLink = computed(() => {
    if (props.role === 'admin') return `/admin/pediatric/patients/${props.patient.id}/growth`;
    if (props.role === 'doctor') return `/doctor/pediatric/growth?patient_id=${props.patient.id}`;
    return null;
});

const profileLink = computed(() => {
    if (props.role === 'admin') return `/admin/pediatric/patients/${props.patient.id}`;
    if (props.role === 'doctor') return `/doctor/pediatric/patients/${props.patient.id}`;
    if (props.role === 'secretary') return `/secretary/pediatric/patients/${props.patient.id}`;
    return null;
});

function formatDate(d) {
    if (!d) return '-';
    try {
        return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch { return d; }
}

const upcomingVaccinations = computed(() =>
    vaccinations.value.filter(v => v.status === 'scheduled').slice(0, 10)
);
const givenVaccinations = computed(() =>
    vaccinations.value.filter(v => v.status === 'given').slice(0, 10)
);

const gender = computed(() => props.patient?.gender || 'male');
</script>

<template>
    <div v-if="data" class="space-y-5">
        <!-- Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border border-emerald-100 p-4">
                <p class="text-2xl font-bold text-emerald-600">{{ stats.total_visits || 0 }}</p>
                <p class="text-xs text-gray-500">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-teal-100 p-4">
                <p class="text-2xl font-bold text-teal-600">{{ stats.growth_records || 0 }}</p>
                <p class="text-xs text-gray-500">{{ isRtl ? 'قياسات النمو' : 'Growth Records' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-100 p-4">
                <p class="text-2xl font-bold text-emerald-600">{{ stats.given_vaccinations || 0 }}</p>
                <p class="text-xs text-gray-500">{{ isRtl ? 'تطعيمات مأخوذة' : 'Given Vaccines' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-red-100 p-4">
                <p class="text-2xl font-bold text-red-500">{{ stats.active_allergies || 0 }}</p>
                <p class="text-xs text-gray-500">{{ isRtl ? 'حساسيات نشطة' : 'Active Allergies' }}</p>
            </div>
        </div>

        <!-- Quick Links -->
        <div v-if="!readonly && (profileLink || vaccinationsLink || growthLink)" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <Link v-if="profileLink" :href="profileLink"
                class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'ملف الطفل' : 'Child Profile' }}</p>
                    <p class="text-[10px] text-gray-400">{{ isRtl ? 'عرض الملف الكامل' : 'View full profile' }}</p>
                </div>
            </Link>
            <Link v-if="vaccinationsLink" :href="vaccinationsLink"
                class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'التطعيمات' : 'Vaccinations' }}</p>
                    <p class="text-[10px] text-gray-400">{{ stats.total_vaccinations || 0 }} {{ isRtl ? 'سجل' : 'records' }}</p>
                </div>
            </Link>
            <Link v-if="growthLink" :href="growthLink"
                class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 transition">
                <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'مخطط النمو' : 'Growth Chart' }}</p>
                    <p class="text-[10px] text-gray-400">{{ stats.growth_records || 0 }} {{ isRtl ? 'قياس' : 'records' }}</p>
                </div>
            </Link>
        </div>

        <!-- Growth Chart -->
        <div v-if="growthRecords.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    {{ isRtl ? 'مخطط النمو' : 'Growth Chart' }}
                </h3>
                <div v-if="stats.latest_weight || stats.latest_height" class="flex items-center gap-3 text-xs text-gray-500">
                    <span v-if="stats.latest_weight"><strong class="text-gray-800">{{ stats.latest_weight }}</strong>{{ isRtl ? ' كغ' : ' kg' }}</span>
                    <span v-if="stats.latest_height"><strong class="text-gray-800">{{ stats.latest_height }}</strong>{{ isRtl ? ' سم' : ' cm' }}</span>
                </div>
            </div>
            <div class="p-5">
                <PediatricGrowthChart :records="growthRecords" :gender="gender" />
            </div>
        </div>

        <!-- Allergies -->
        <div v-if="allergies.length" class="bg-white rounded-xl border border-red-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-red-100 bg-red-50/50">
                <h3 class="text-sm font-bold text-red-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    {{ isRtl ? 'الحساسيات' : 'Allergies' }}
                </h3>
            </div>
            <div class="divide-y divide-red-50">
                <div v-for="a in allergies" :key="a.id" class="px-5 py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-red-700">{{ a.allergen || a.name }}</p>
                            <p v-if="a.reaction" class="text-xs text-gray-600 mt-0.5">{{ a.reaction }}</p>
                        </div>
                        <span v-if="a.severity" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                            :class="a.severity === 'severe' ? 'bg-red-100 text-red-700' : a.severity === 'moderate' ? 'bg-amber-100 text-amber-700' : 'bg-yellow-50 text-amber-700'">
                            {{ a.severity }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chronic Conditions (read-only oversight) -->
        <div v-if="chronicConditions.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الأمراض المزمنة' : 'Chronic Conditions' }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="c in chronicConditions" :key="c.id" class="px-5 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ isRtl ? (c.condition_name_ar || c.condition_name) : c.condition_name }}</p>
                        <p v-if="c.diagnosed_date" class="text-[10px] text-gray-400">{{ isRtl ? 'تشخيص' : 'Dx' }}: {{ formatDate(c.diagnosed_date) }}</p>
                    </div>
                    <span v-if="c.severity" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                        :class="c.severity === 'severe' ? 'bg-red-100 text-red-700' : c.severity === 'moderate' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-50 text-emerald-700'">
                        {{ c.severity }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Developmental Milestones (read-only) -->
        <div v-if="milestones.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'المعالم التطورية' : 'Developmental Milestones' }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="m in milestones.slice(0, 12)" :key="m.id" class="px-5 py-2.5 flex items-center justify-between gap-3">
                    <p class="text-sm text-gray-700 truncate">{{ isRtl ? (m.milestone_name_ar || m.milestone_name_en) : m.milestone_name_en }}</p>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                        :class="m.status === 'achieved' ? 'bg-emerald-50 text-emerald-700' : m.status === 'emerging' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-600'">
                        {{ m.status === 'achieved' ? (isRtl ? 'مكتمل' : 'Achieved') : m.status === 'emerging' ? (isRtl ? 'ناشئ' : 'Emerging') : (isRtl ? 'متأخر' : 'Not yet') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Screening Tests (read-only) -->
        <div v-if="screeningTests.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'فحوص الكشف' : 'Screening Tests' }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="s in screeningTests" :key="s.id" class="px-5 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ s.test_type }}</p>
                        <p v-if="s.test_date" class="text-[10px] text-gray-400">{{ formatDate(s.test_date) }}<span v-if="s.total_score !== null"> · {{ isRtl ? 'النتيجة' : 'Score' }}: {{ s.total_score }}</span></p>
                    </div>
                    <span v-if="s.result" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-[#1B365D]">{{ s.result }}</span>
                </div>
            </div>
        </div>

        <!-- Upcoming Vaccinations -->
        <div v-if="upcomingVaccinations.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تطعيمات قادمة' : 'Upcoming Vaccinations' }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="v in upcomingVaccinations" :key="v.id" class="px-5 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ v.vaccine_name }}</p>
                        <p class="text-[10px] text-gray-400">{{ formatDate(v.scheduled_date) }}</p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">
                        {{ isRtl ? 'مجدول' : 'Scheduled' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Given Vaccinations -->
        <div v-if="givenVaccinations.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تطعيمات مأخوذة' : 'Given Vaccinations' }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="v in givenVaccinations" :key="v.id" class="px-5 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ v.vaccine_name }}</p>
                        <p class="text-[10px] text-gray-400">{{ formatDate(v.given_date || v.scheduled_date) }}</p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">
                        {{ isRtl ? 'مأخوذ' : 'Given' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!growthRecords.length && !vaccinations.length && !allergies.length" class="bg-gray-50 rounded-xl p-6 text-center">
            <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد بيانات أطفال' : 'No pediatric data recorded' }}</p>
        </div>
    </div>

    <div v-else class="bg-gray-50 rounded-2xl p-8 text-center">
        <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد بيانات أطفال' : 'No pediatric data available' }}</p>
    </div>
</template>
