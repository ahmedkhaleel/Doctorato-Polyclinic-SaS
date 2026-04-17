<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    patient: { type: Object, required: true },
    activeSpecialties: { type: Array, default: () => [] },
    dermaData: { type: Object, default: null },
    dentalData: { type: Object, default: null },
    pediatricData: { type: Object, default: null },
    financialSummary: { type: Object, default: null },
    visits: { type: Array, default: () => [] },
});

const emit = defineEmits(['changeTab']);

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const hasMedicalAlerts = computed(() => {
    return props.patient?.allergies
        || props.patient?.latex_allergy
        || props.patient?.has_bleeding_disorder
        || props.patient?.takes_blood_thinners
        || props.patient?.has_heart_condition
        || props.patient?.has_diabetes
        || props.patient?.is_pregnant
        || props.patient?.is_breastfeeding;
});

const upcomingVisits = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return (props.visits || [])
        .filter(v => v.visit_date >= today && v.status !== 'completed' && v.status !== 'cancelled')
        .slice(0, 5);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Medical Alerts -->
        <div v-if="hasMedicalAlerts" class="bg-gradient-to-r from-red-50 to-amber-50 border border-red-200 rounded-2xl p-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-red-800 mb-2">{{ isRtl ? 'تنبيهات طبية مهمة' : 'Medical Alerts' }}</h3>
                    <div class="flex flex-wrap gap-2">
                        <span v-if="patient.allergies" class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                            {{ isRtl ? 'حساسية:' : 'Allergies:' }} {{ patient.allergies }}
                        </span>
                        <span v-if="patient.latex_allergy" class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                            {{ isRtl ? 'حساسية اللاتكس' : 'Latex Allergy' }}
                        </span>
                        <span v-if="patient.has_bleeding_disorder" class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                            {{ isRtl ? 'اضطراب نزيف' : 'Bleeding Disorder' }}
                        </span>
                        <span v-if="patient.takes_blood_thinners" class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                            {{ isRtl ? 'مميعات دم' : 'Blood Thinners' }}{{ patient.blood_thinner_name ? ': ' + patient.blood_thinner_name : '' }}
                        </span>
                        <span v-if="patient.has_heart_condition" class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                            {{ isRtl ? 'مشاكل قلبية' : 'Heart Condition' }}
                        </span>
                        <span v-if="patient.has_diabetes" class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                            {{ isRtl ? 'سكري' : 'Diabetes' }}{{ patient.diabetes_type ? ` (${patient.diabetes_type})` : '' }}
                        </span>
                        <span v-if="patient.is_pregnant" class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-100 text-[#C4A265] text-xs font-semibold">
                            {{ isRtl ? 'حامل' : 'Pregnant' }}
                        </span>
                        <span v-if="patient.is_breastfeeding" class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-100 text-[#C4A265] text-xs font-semibold">
                            {{ isRtl ? 'مرضعة' : 'Breastfeeding' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Specialty Summary Cards -->
        <div v-if="activeSpecialties.length" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Derma -->
            <button v-if="activeSpecialties.includes('derma')" @click="emit('changeTab', 'derma')"
                class="group relative text-start bg-gradient-to-br from-[#FBF7EE] to-[#F5EDD8] rounded-2xl p-5 border border-[#C4A265]/30 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-[#C4A265]/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    </div>
                    <svg class="w-4 h-4 text-[#C4A265]/50 group-hover:translate-x-1 transition-transform" :class="{ 'rotate-180 group-hover:-translate-x-1': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-800 mb-1">{{ isRtl ? 'الجلدية والتجميل' : 'Dermatology' }}</h3>
                <div class="flex items-baseline gap-3 mt-2">
                    <span class="text-2xl font-bold text-[#C4A265]">{{ dermaData?.stats?.total_visits || 0 }}</span>
                    <span class="text-xs text-gray-500">{{ isRtl ? 'زيارة' : 'visits' }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    {{ dermaData?.stats?.total_photos || 0 }} {{ isRtl ? 'صورة' : 'photos' }} ·
                    {{ dermaData?.prescriptions?.length || 0 }} {{ isRtl ? 'وصفة' : 'prescriptions' }}
                </p>
            </button>

            <!-- Dental -->
            <button v-if="activeSpecialties.includes('dental')" @click="emit('changeTab', 'dental')"
                class="group relative text-start bg-gradient-to-br from-slate-50 to-slate-50 rounded-2xl p-5 border border-slate-200 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2.5c-2 0-3.5 1.5-3.5 3.5 0 1 .4 2 .8 2.8.6 1 .7 2.2.7 3.2v4c0 1 .5 2 1.5 2s1.5-1 1.5-2v-2.5c0-.3.2-.5.5-.5s.5.2.5.5V16c0 1 .5 2 1.5 2s1.5-1 1.5-2v-4c0-1 .1-2.2.7-3.2.4-.8.8-1.8.8-2.8 0-2-1.5-3.5-3.5-3.5z" /></svg>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" :class="{ 'rotate-180 group-hover:-translate-x-1': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-800 mb-1">{{ isRtl ? 'طب الأسنان' : 'Dental' }}</h3>
                <div class="flex items-baseline gap-3 mt-2">
                    <span class="text-2xl font-bold text-[#1B365D]">{{ dentalData?.stats?.total_treatments || 0 }}</span>
                    <span class="text-xs text-gray-500">{{ isRtl ? 'علاج' : 'treatments' }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    {{ dentalData?.charts?.length || 0 }} {{ isRtl ? 'مخطط' : 'charts' }} ·
                    {{ dentalData?.xrays?.length || 0 }} {{ isRtl ? 'أشعة' : 'x-rays' }}
                </p>
            </button>

            <!-- Pediatric -->
            <button v-if="activeSpecialties.includes('pediatric')" @click="emit('changeTab', 'pediatric')"
                class="group relative text-start bg-gradient-to-br from-emerald-50 to-emerald-50 rounded-2xl p-5 border border-emerald-200 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z" /></svg>
                    </div>
                    <svg class="w-4 h-4 text-emerald-400 group-hover:translate-x-1 transition-transform" :class="{ 'rotate-180 group-hover:-translate-x-1': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-800 mb-1">{{ isRtl ? 'طب الأطفال' : 'Pediatric' }}</h3>
                <div class="flex items-baseline gap-3 mt-2">
                    <span class="text-2xl font-bold text-emerald-600">{{ pediatricData?.stats?.growth_records || 0 }}</span>
                    <span class="text-xs text-gray-500">{{ isRtl ? 'قياس نمو' : 'growth records' }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    {{ pediatricData?.stats?.total_vaccinations || 0 }} {{ isRtl ? 'تطعيم' : 'vaccinations' }} ·
                    {{ pediatricData?.stats?.active_allergies || 0 }} {{ isRtl ? 'حساسية' : 'allergies' }}
                </p>
            </button>
        </div>

        <!-- Financial + Upcoming Visits Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Financial Summary -->
            <div v-if="financialSummary" class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الوضع المالي' : 'Financial Status' }}</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ isRtl ? 'إجمالي الفواتير' : 'Total Invoiced' }}</span>
                        <span class="text-sm font-bold text-gray-800">{{ (financialSummary.total_invoiced || 0).toLocaleString() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ isRtl ? 'المدفوع' : 'Paid' }}</span>
                        <span class="text-sm font-bold text-emerald-600">{{ (financialSummary.total_paid || 0).toLocaleString() }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <span class="text-xs font-semibold text-gray-700">{{ isRtl ? 'المتبقي' : 'Outstanding' }}</span>
                        <span class="text-lg font-black" :class="(financialSummary.outstanding_balance || 0) > 0 ? 'text-red-500' : 'text-emerald-600'">
                            {{ (financialSummary.outstanding_balance || 0).toLocaleString() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Upcoming Visits -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'المواعيد القادمة' : 'Upcoming Visits' }}</h3>
                </div>
                <div v-if="upcomingVisits.length" class="space-y-2">
                    <div v-for="v in upcomingVisits" :key="v.id" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 text-[#1B365D] flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-[9px] font-bold uppercase">{{ new Date(v.visit_date).toLocaleDateString('en', { month: 'short' }) }}</span>
                            <span class="text-xs font-black leading-none">{{ new Date(v.visit_date).getDate() }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ v.service?.name_ar || v.service?.name_en || v.visit_type || (isRtl ? 'زيارة' : 'Visit') }}</p>
                            <p class="text-[10px] text-gray-500">{{ v.doctor?.name_en || v.doctor?.name_ar || '-' }}</p>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                            :class="v.module === 'dental' ? 'bg-slate-50 text-[#1B365D]' : v.module === 'pediatric' ? 'bg-emerald-50 text-emerald-700' : 'bg-[#C4A265]/10 text-[#C4A265]'">
                            {{ v.module === 'dental' ? (isRtl ? 'أسنان' : 'Dental') : v.module === 'pediatric' ? (isRtl ? 'أطفال' : 'Peds') : (isRtl ? 'جلدية' : 'Derma') }}
                        </span>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا توجد مواعيد قادمة' : 'No upcoming visits' }}</p>
            </div>
        </div>

        <!-- No Specialties State -->
        <div v-if="!activeSpecialties.length" class="bg-gray-50 rounded-2xl p-8 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-white flex items-center justify-center shadow-sm mb-3">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-700">{{ isRtl ? 'لم يبدأ علاج المريض بعد' : 'No treatments started yet' }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ isRtl ? 'ستظهر التخصصات الطبية هنا عند إضافة أول زيارة' : 'Medical specialties will appear here after first visit' }}</p>
        </div>
    </div>
</template>
