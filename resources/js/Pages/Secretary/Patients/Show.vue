<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import SpecialtyTabs from '@/Components/Patient/SpecialtyTabs.vue';
import EngagementCard from '@/Components/Patient/EngagementCard.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    financialSummary: Object,
    activeSpecialties: { type: Array, default: () => [] },
    dermaData: { type: Object, default: null },
    dentalData: { type: Object, default: null },
    pediatricData: { type: Object, default: null },
    engagement: { type: Object, default: null },
    doctors: Array,
});

const genderLabels = { male: isRtl.value ? 'ذكر' : 'Male', female: isRtl.value ? 'أنثى' : 'Female' };

const referralLabels = {
    walk_in: isRtl.value ? 'حضور مباشر' : 'Walk-in',
    social_media: 'Social Media',
    google: 'Google',
    friend: 'Friend',
    doctor: 'Doctor',
    advertisement: 'Advertisement',
    other: 'Other',
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const { formatCurrency } = useCurrency();

const visits = computed(() => props.patient?.visits || []);
const invoices = computed(() => props.patient?.invoices || []);
const prescriptions = computed(() => props.patient?.prescriptions || []);
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-6">
            <Link href="/secretary/patients" class="inline-flex items-center gap-1 text-xs text-teal-600 hover:text-teal-800 font-medium mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                {{ isRtl ? 'العودة للمرضى' : 'Back to Patients' }}
            </Link>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-4">
                    <div v-if="patient.photo" class="w-14 h-14 rounded-2xl overflow-hidden ring-2 ring-teal-100 flex-shrink-0">
                        <img
                            :src="patient.photo.startsWith('http') ? patient.photo : `/storage/${patient.photo}`"
                            :alt="patient.full_name"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    <div v-else class="w-14 h-14 rounded-2xl flex-shrink-0 flex items-center justify-center text-white text-xl font-bold bg-gradient-to-br from-teal-500 to-[#1B365D] shadow-md shadow-teal-500/20">
                        {{ patient.full_name?.charAt(0) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ patient.full_name }}</h1>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-sm font-mono font-semibold text-teal-600">{{ patient.file_number }}</span>
                            <span
                                class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                :class="patient.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200'"
                            >
                                {{ patient.is_active ? (isRtl ? 'نشط' : 'Active') : (isRtl ? 'غير نشط' : 'Inactive') }}
                            </span>
                        </div>
                    </div>
                </div>
                <Link
                    :href="`/secretary/patients/${patient.id}/edit`"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    {{ isRtl ? 'تعديل' : 'Edit' }}
                </Link>
            </div>
        </div>

        <!-- Patient Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
            <!-- Contact -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">{{ isRtl ? 'معلومات الاتصال' : 'Contact Details' }}</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'الهاتف' : 'Phone' }}</span>
                        <span class="font-medium text-gray-800">{{ patient.phone || '-' }}</span>
                    </div>
                    <div v-if="patient.phone2" class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'الهاتف 2' : 'Phone 2' }}</span>
                        <span class="font-medium text-gray-800">{{ patient.phone2 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'البريد' : 'Email' }}</span>
                        <span class="font-medium text-gray-800">{{ patient.email || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'العنوان' : 'Address' }}</span>
                        <span class="font-medium text-gray-800 ltr:text-right rtl:text-left max-w-[180px]">{{ patient.address || '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Personal -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">{{ isRtl ? 'البيانات الشخصية' : 'Personal Details' }}</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'الجنس' : 'Gender' }}</span>
                        <span class="font-medium text-gray-800 capitalize">{{ genderLabels[patient.gender] || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }}</span>
                        <span class="font-medium text-gray-800">{{ formatDate(patient.date_of_birth) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'الجنسية' : 'Nationality' }}</span>
                        <span class="font-medium text-gray-800">{{ patient.nationality || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'المهنة' : 'Occupation' }}</span>
                        <span class="font-medium text-gray-800">{{ patient.occupation || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'الإحالة' : 'Referral' }}</span>
                        <span class="font-medium text-gray-800">{{ referralLabels[patient.referral_source] || '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Medical Notes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">{{ isRtl ? 'ملاحظات طبية' : 'Medical Notes' }}</h3>
                <p v-if="patient.medical_notes" class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ patient.medical_notes }}</p>
                <p v-else class="text-sm text-gray-400 italic">{{ isRtl ? 'لا توجد ملاحظات طبية' : 'No medical notes recorded' }}</p>
            </div>
        </div>

        <!-- Engagement (loyalty + referrals + active codes) -->
        <EngagementCard v-if="engagement" :engagement="engagement" />

        <!-- Unified Specialty Tabs (read-only for secretary) -->
        <SpecialtyTabs
            role="secretary"
            :readonly="true"
            :patient="patient"
            :active-specialties="activeSpecialties"
            :derma-data="dermaData"
            :dental-data="dentalData"
            :pediatric-data="pediatricData"
            :visits="visits"
            :invoices="invoices"
            :prescriptions="prescriptions"
            :financial-summary="financialSummary"
        />
    </div>
</template>
