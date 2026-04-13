<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
});

const activeTab = ref('info');

const genderLabels = computed(() => ({
    male: isRtl.value ? 'ذكر' : 'Male',
    female: isRtl.value ? 'أنثى' : 'Female',
}));

const guardianRelationLabels = computed(() => ({
    father: isRtl.value ? 'أب' : 'Father',
    mother: isRtl.value ? 'أم' : 'Mother',
    grandfather: isRtl.value ? 'جد' : 'Grandfather',
    grandmother: isRtl.value ? 'جدة' : 'Grandmother',
    sibling: isRtl.value ? 'أخ/أخت' : 'Sibling',
    other: isRtl.value ? 'أخرى' : 'Other',
}));

const birthTypeLabels = computed(() => ({
    normal: isRtl.value ? 'طبيعية' : 'Normal',
    cesarean: isRtl.value ? 'قيصرية' : 'Cesarean',
    vacuum: isRtl.value ? 'شفط' : 'Vacuum',
    forceps: isRtl.value ? 'ملقط' : 'Forceps',
}));

const feedingTypeLabels = computed(() => ({
    breastfeeding: isRtl.value ? 'رضاعة طبيعية' : 'Breastfeeding',
    formula: isRtl.value ? 'رضاعة صناعية' : 'Formula',
    mixed: isRtl.value ? 'مختلطة' : 'Mixed',
}));

const visitStatusColors = {
    waiting: 'bg-amber-50 text-amber-700 border-amber-200',
    in_progress: 'bg-blue-50 text-blue-700 border-blue-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

const visitStatusLabels = computed(() => ({
    waiting: isRtl.value ? 'انتظار' : 'Waiting',
    in_progress: isRtl.value ? 'جارية' : 'In Progress',
    completed: isRtl.value ? 'مكتملة' : 'Completed',
    cancelled: isRtl.value ? 'ملغاة' : 'Cancelled',
}));

function calculateAge(dob) {
    if (!dob) return '-';
    const birth = new Date(dob);
    const now = new Date();
    const diffMs = now - birth;
    const years = Math.floor(diffMs / (365.25 * 24 * 60 * 60 * 1000));
    const months = Math.floor((diffMs % (365.25 * 24 * 60 * 60 * 1000)) / (30.44 * 24 * 60 * 60 * 1000));
    if (years < 1) return isRtl.value ? `${months} شهر` : `${months} months`;
    if (years < 3) return isRtl.value ? `${years} سنة و ${months} شهر` : `${years}y ${months}m`;
    return isRtl.value ? `${years} سنة` : `${years} years`;
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const complicationLabels = computed(() => ({
    jaundice: isRtl.value ? 'يرقان' : 'Jaundice',
    hypoxia: isRtl.value ? 'نقص أكسجين' : 'Hypoxia',
    nicu: isRtl.value ? 'حضانة' : 'NICU',
    none: isRtl.value ? 'لا يوجد' : 'None',
}));

const headerLoaded = ref(false);
const contentLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { contentLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- HERO HEADER -->
        <div
            class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
        >
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 30% 50%, #4CAF50 0%, transparent 40%), radial-gradient(circle at 70% 50%, #0d9488 0%, transparent 50%)"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <Link href="/secretary/pediatric/patients" class="inline-flex items-center gap-1.5 text-gray-400 hover:text-white transition-colors text-sm">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        {{ isRtl ? 'العودة للقائمة' : 'Back to List' }}
                    </Link>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl font-bold bg-gradient-to-br from-[#4CAF50] to-[#0d9488] shadow-lg">
                        {{ patient.full_name?.charAt(0) }}
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ patient.full_name }}</h1>
                        <div class="flex items-center gap-3 mt-2 flex-wrap">
                            <span class="text-xs font-mono font-semibold text-[#4CAF50]">{{ patient.file_number || '-' }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-white/10 text-white border border-white/20">
                                {{ calculateAge(patient.date_of_birth) }}
                            </span>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                :class="patient.gender === 'male' ? 'bg-blue-500/20 text-blue-300 border-blue-400/30' : 'bg-pink-500/20 text-pink-300 border-pink-400/30'"
                            >
                                {{ genderLabels[patient.gender] || '-' }}
                            </span>
                            <span v-if="patient.blood_type" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-500/20 text-red-300 border border-red-400/30">
                                {{ patient.blood_type }}
                            </span>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <Link
                            :href="`/secretary/pediatric/patients/${patient.id}/edit`"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            {{ isRtl ? 'تعديل' : 'Edit' }}
                        </Link>
                        <Link
                            :href="`/secretary/pediatric/visits/create?patient_id=${patient.id}`"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-[#4CAF50] hover:bg-[#43A047] transition-all shadow-lg shadow-[#4CAF50]/20"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ isRtl ? 'حجز زيارة' : 'Book Visit' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="transition-all duration-500" :class="contentLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <!-- Tab Navigation -->
            <div class="flex gap-1 mb-6 bg-gray-100 rounded-xl p-1 overflow-x-auto">
                <button
                    v-for="tab in [
                        { id: 'info', label: isRtl ? 'المعلومات' : 'Information' },
                        { id: 'birth', label: isRtl ? 'تاريخ الولادة' : 'Birth History' },
                        { id: 'visits', label: isRtl ? 'الزيارات' : 'Visits' },
                    ]"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-all whitespace-nowrap"
                    :class="activeTab === tab.id ? 'bg-white text-[#4CAF50] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- TAB: Information -->
            <div v-show="activeTab === 'info'" class="space-y-6">
                <!-- Patient Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-[#4CAF50]/5 to-transparent">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#4CAF50]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            {{ isRtl ? 'معلومات الطفل' : 'Child Information' }}
                        </h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الاسم الكامل' : 'Full Name' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.full_name }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ formatDate(patient.date_of_birth) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'العمر' : 'Age' }}</p>
                                <p class="text-sm font-semibold text-[#4CAF50] mt-1">{{ calculateAge(patient.date_of_birth) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الجنس' : 'Gender' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ genderLabels[patient.gender] || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'فصيلة الدم' : 'Blood Type' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.blood_type || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الجنسية' : 'Nationality' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.nationality || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الهاتف' : 'Phone' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1 dir-ltr">{{ patient.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'رقم الملف' : 'File Number' }}</p>
                                <p class="text-sm font-mono font-bold text-[#0d9488] mt-1">{{ patient.file_number || '-' }}</p>
                            </div>
                        </div>
                        <div v-if="patient.address" class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'العنوان' : 'Address' }}</p>
                            <p class="text-sm text-gray-700 mt-1">{{ patient.address }}</p>
                        </div>
                    </div>
                </div>

                <!-- Guardian Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-[#0d9488]/5 to-transparent">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            {{ isRtl ? 'معلومات ولي الأمر' : 'Guardian Information' }}
                        </h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'اسم ولي الأمر' : 'Guardian Name' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.guardian_name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'صلة القرابة' : 'Relation' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ guardianRelationLabels[patient.guardian_relation] || patient.guardian_relation || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الهاتف' : 'Phone' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1 dir-ltr">{{ patient.guardian_phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'هاتف بديل' : 'Alt. Phone' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1 dir-ltr">{{ patient.guardian_phone2 || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'البريد' : 'Email' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1 dir-ltr">{{ patient.guardian_email || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'المهنة' : 'Occupation' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.guardian_occupation || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Birth History -->
            <div v-show="activeTab === 'birth'" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-pink-50/50 to-transparent">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                            {{ isRtl ? 'تاريخ الولادة' : 'Birth History' }}
                        </h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'نوع الولادة' : 'Birth Type' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ birthTypeLabels[patient.birth_type] || patient.birth_type || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'مكان الولادة' : 'Birth Place' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.birth_place || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'عمر الحمل' : 'Gestational Age' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.gestational_age_weeks ? `${patient.gestational_age_weeks} ${isRtl ? 'أسبوع' : 'weeks'}` : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الوزن عند الولادة' : 'Birth Weight' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.birth_weight_kg ? `${patient.birth_weight_kg} ${isRtl ? 'كغ' : 'kg'}` : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الطول عند الولادة' : 'Birth Length' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.birth_length_cm ? `${patient.birth_length_cm} ${isRtl ? 'سم' : 'cm'}` : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'محيط الرأس' : 'Head Circ.' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.birth_head_circumference_cm ? `${patient.birth_head_circumference_cm} ${isRtl ? 'سم' : 'cm'}` : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'أبغار 1 دقيقة' : 'Apgar 1min' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.apgar_1min ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'أبغار 5 دقائق' : 'Apgar 5min' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.apgar_5min ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'نوع التغذية' : 'Feeding Type' }}</p>
                                <p class="text-sm font-semibold text-gray-800 mt-1">{{ feedingTypeLabels[patient.feeding_type] || patient.feeding_type || '-' }}</p>
                            </div>
                        </div>

                        <!-- Complications -->
                        <div v-if="patient.birth_complications && patient.birth_complications.length" class="mt-5 pt-5 border-t border-gray-100">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase mb-2">{{ isRtl ? 'المضاعفات' : 'Complications' }}</p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="comp in patient.birth_complications"
                                    :key="comp"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="comp === 'none' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ complicationLabels[comp] || comp }}
                                </span>
                            </div>
                        </div>

                        <div v-if="patient.nicu_days" class="mt-3">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'أيام الحضانة' : 'NICU Days' }}</p>
                            <p class="text-sm font-semibold text-gray-800 mt-1">{{ patient.nicu_days }} {{ isRtl ? 'يوم' : 'days' }}</p>
                        </div>

                        <div v-if="patient.pregnancy_complications" class="mt-3">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'مضاعفات الحمل' : 'Pregnancy Complications' }}</p>
                            <p class="text-sm text-gray-700 mt-1">{{ patient.pregnancy_complications }}</p>
                        </div>
                    </div>
                </div>

                <!-- Family History -->
                <div v-if="patient.family_history && patient.family_history.length" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50/50 to-transparent">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            {{ isRtl ? 'التاريخ العائلي' : 'Family History' }}
                        </h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <div
                            v-for="(item, idx) in patient.family_history"
                            :key="idx"
                            class="flex items-start gap-3 p-3 rounded-xl bg-gray-50/80"
                        >
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="text-xs font-bold text-amber-600">{{ idx + 1 }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800">{{ item.condition }}</p>
                                <div v-if="item.affected_members?.length" class="flex flex-wrap gap-1.5 mt-1.5">
                                    <span v-for="member in item.affected_members" :key="member" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#4CAF50]/10 text-[#4CAF50]">
                                        {{ member }}
                                    </span>
                                </div>
                                <p v-if="item.details" class="text-xs text-gray-500 mt-1">{{ item.details }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Visits -->
            <div v-show="activeTab === 'visits'">
                <div class="space-y-3">
                    <div
                        v-for="visit in (patient.visits || [])"
                        :key="visit.id"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all p-4 sm:p-5"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-bold text-gray-900">{{ formatDate(visit.visit_date || visit.created_at) }}</span>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="visitStatusColors[visit.status] || 'bg-gray-50 text-gray-500 border-gray-200'"
                                    >
                                        {{ visitStatusLabels[visit.status] || visit.status }}
                                    </span>
                                </div>
                                <p v-if="visit.reason || visit.notes" class="text-xs text-gray-500 mt-1 truncate">{{ visit.reason || visit.notes }}</p>
                            </div>
                            <Link
                                :href="`/secretary/pediatric/visits/${visit.id}`"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#0d9488] bg-[#0d9488]/5 hover:bg-[#0d9488]/10 rounded-xl transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                {{ isRtl ? 'عرض' : 'View' }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="!patient.visits || patient.visits.length === 0" class="py-12 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد زيارات بعد' : 'No visits yet' }}</p>
                        <Link
                            :href="`/secretary/pediatric/visits/create?patient_id=${patient.id}`"
                            class="inline-flex items-center gap-1.5 mt-3 px-4 py-2 text-sm font-semibold text-[#4CAF50] bg-[#4CAF50]/5 hover:bg-[#4CAF50]/10 rounded-xl transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ isRtl ? 'حجز زيارة' : 'Book Visit' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
