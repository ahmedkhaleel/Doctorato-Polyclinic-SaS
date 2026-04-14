<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    financialSummary: Object,
    doctors: Array,
});

const activeTab = ref('visits');

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

const visitStatusColors = {
    waiting: 'bg-amber-50 text-amber-700 border-amber-200',
    in_progress: 'bg-blue-50 text-blue-700 border-blue-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

const invoiceStatusColors = {
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    partial: 'bg-amber-50 text-amber-700 border-amber-200',
    unpaid: 'bg-red-50 text-red-700 border-red-200',
    cancelled: 'bg-gray-50 text-gray-500 border-gray-200',
};

const bundleStatusColors = {
    pending: 'bg-gray-50 text-gray-600 border-gray-200',
    confirmed: 'bg-blue-50 text-blue-700 border-blue-200',
    in_progress: 'bg-amber-50 text-amber-700 border-amber-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

const bundleServiceStatusColors = {
    pending: 'bg-gray-50 text-gray-600 border-gray-200',
    in_progress: 'bg-blue-50 text-blue-700 border-blue-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

function bundleProgress(booking) {
    if (!booking.bundle_services?.length) return 0;
    const total = booking.bundle_services.reduce((s, bs) => s + (bs.sessions_count || 0), 0);
    const done = booking.bundle_services.reduce((s, bs) => s + (bs.completed_sessions || 0), 0);
    return total > 0 ? Math.round((done / total) * 100) : 0;
}

const hasDentalVisits = computed(() => (props.patient?.visits || []).some(v => v.module === 'dental'));
const hasPediatricVisits = computed(() => (props.patient?.visits || []).some(v => v.module === 'pediatric'));

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const { formatCurrency, currencyCode } = useCurrency();

function getDoctorName(doctorId) {
    if (!doctorId || !props.doctors) return '-';
    const doc = props.doctors.find(d => d.id === doctorId);
    return doc?.name_en || doc?.name || '-';
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-6">
            <Link href="/secretary/patients" class="inline-flex items-center gap-1 text-xs text-teal-600 hover:text-teal-800 font-medium mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Back to Patients
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
                    <div v-else class="w-14 h-14 rounded-2xl flex-shrink-0 flex items-center justify-center text-white text-xl font-bold bg-gradient-to-br from-teal-500 to-cyan-500 shadow-md shadow-teal-500/20">
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
                                {{ patient.is_active ? 'Active' : 'Inactive' }}
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

        <!-- Patient Info Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
            <!-- Contact & Personal -->
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

            <!-- Personal Details -->
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
                    <div v-if="patient.referred_by" class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'مُحال من' : 'Referred By' }}</span>
                        <span class="font-medium text-gray-800">{{ patient.referred_by }}</span>
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

        <!-- Dental Quick Links -->
        <div v-if="$page.props.modules?.dental?.enabled && hasDentalVisits" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 mb-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                {{ isRtl ? 'طب الأسنان' : 'Dental' }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <Link :href="`/secretary/dental/patient-chart/${patient.id}`"
                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 transition group"
                >
                    <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-teal-600">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</p>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'عرض مخطط المريض' : 'View patient chart' }}</p>
                    </div>
                </Link>
                <Link href="/secretary/dental/treatment-plans"
                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-purple-300 hover:bg-purple-50/50 transition group"
                >
                    <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-purple-600">{{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}</p>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'عرض خطط العلاج' : 'View treatment plans' }}</p>
                    </div>
                </Link>
                <Link href="/secretary/dental/lab-orders"
                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-cyan-300 hover:bg-cyan-50/50 transition group"
                >
                    <div class="w-9 h-9 rounded-lg bg-cyan-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-cyan-600">{{ isRtl ? 'طلبات المعمل' : 'Lab Orders' }}</p>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'عرض طلبات المعمل' : 'View lab orders' }}</p>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Pediatric Quick Links -->
        <div v-if="$page.props.modules?.pediatric?.enabled && hasPediatricVisits" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 mb-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                {{ isRtl ? 'طب الأطفال' : 'Pediatric' }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <Link :href="`/secretary/pediatric/patients/${patient.id}`"
                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-green-300 hover:bg-green-50/50 transition group"
                >
                    <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-green-600">{{ isRtl ? 'ملف الطفل' : 'Child Profile' }}</p>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'عرض ملف المريض' : 'View patient profile' }}</p>
                    </div>
                </Link>
                <Link :href="`/secretary/pediatric/vaccinations?patient_id=${patient.id}`"
                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition group"
                >
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-emerald-600">{{ isRtl ? 'التطعيمات' : 'Vaccinations' }}</p>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'عرض سجل التطعيمات' : 'View vaccination records' }}</p>
                    </div>
                </Link>
                <Link :href="`/secretary/pediatric/growth?patient_id=${patient.id}`"
                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 transition group"
                >
                    <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-teal-600">{{ isRtl ? 'سجل النمو' : 'Growth Chart' }}</p>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'عرض سجلات النمو' : 'View growth records' }}</p>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 mb-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">{{ isRtl ? 'الملخص المالي' : 'Financial Summary' }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 sm:gap-4">
                <div class="text-center p-4 bg-gray-50 rounded-xl">
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold mb-1">{{ isRtl ? 'إجمالي الفواتير' : 'Total Invoiced' }}</p>
                    <p class="text-lg font-bold text-gray-800">{{ formatCurrency(financialSummary?.total_invoiced) }}</p>
                </div>
                <div class="text-center p-4 bg-emerald-50 rounded-xl">
                    <p class="text-[10px] text-emerald-600 uppercase tracking-wider font-semibold mb-1">{{ isRtl ? 'إجمالي المدفوع' : 'Total Paid' }}</p>
                    <p class="text-lg font-bold text-emerald-700">{{ formatCurrency(financialSummary?.total_paid) }}</p>
                </div>
                <div class="text-center p-4 rounded-xl" :class="Number(financialSummary?.outstanding || 0) > 0 ? 'bg-red-50' : 'bg-gray-50'">
                    <p class="text-[10px] uppercase tracking-wider font-semibold mb-1" :class="Number(financialSummary?.outstanding || 0) > 0 ? 'text-red-600' : 'text-gray-500'">{{ isRtl ? 'المتبقي' : 'Outstanding' }}</p>
                    <p class="text-lg font-bold" :class="Number(financialSummary?.outstanding || 0) > 0 ? 'text-red-600' : 'text-gray-800'">{{ formatCurrency(financialSummary?.outstanding) }}</p>
                </div>
                <div class="text-center p-4 bg-teal-50 rounded-xl">
                    <p class="text-[10px] text-teal-600 uppercase tracking-wider font-semibold mb-1">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
                    <p class="text-lg font-bold text-teal-700">{{ financialSummary?.total_visits ?? 0 }}</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-xl">
                    <p class="text-[10px] text-blue-600 uppercase tracking-wider font-semibold mb-1">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
                    <p class="text-lg font-bold text-blue-700">{{ financialSummary?.completed_visits ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-100 px-4 sm:px-6">
                <nav class="flex gap-4 sm:gap-6 -mb-px overflow-x-auto scrollbar-none">
                    <button
                        v-for="tab in [
                            { key: 'visits', label: 'Recent Visits' },
                            { key: 'packages', label: 'Packages' },
                            { key: 'invoices', label: 'Invoices' },
                            { key: 'prescriptions', label: 'Prescriptions' },
                        ]"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        class="py-4 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                        :class="activeTab === tab.key
                            ? 'text-teal-600 border-teal-500'
                            : 'text-gray-400 border-transparent hover:text-gray-600 hover:border-gray-200'"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Recent Visits Tab -->
            <div v-if="activeTab === 'visits'" class="p-4 sm:p-6">
                <div v-if="patient.visits?.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 rounded-lg">
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                <th class="ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="visit in patient.visits" :key="visit.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 text-gray-800">{{ formatDate(visit.visit_date) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ visit.service?.name_en || visit.visit_type || '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ visit.doctor?.name_en || getDoctorName(visit.doctor_id) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border capitalize" :class="visitStatusColors[visit.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                        {{ visit.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 ltr:text-right rtl:text-left">
                                    <Link :href="`/secretary/visits/${visit.id}`" class="text-teal-600 hover:text-teal-800 text-xs font-semibold transition-colors">{{ isRtl ? 'عرض' : 'View' }}</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="py-12 text-center">
                    <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد زيارات' : 'No visits recorded' }}</p>
                </div>
            </div>

            <!-- Packages Tab -->
            <div v-if="activeTab === 'packages'" class="p-4 sm:p-6">
                <div v-if="patient.package_bundle_bookings?.length > 0" class="space-y-5">
                    <div v-for="booking in patient.package_bundle_bookings" :key="booking.id" class="border border-gray-100 rounded-2xl overflow-hidden">
                        <!-- Booking Header -->
                        <div class="flex flex-wrap items-center justify-between gap-3 p-3 sm:p-4 bg-gray-50/50 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ booking.package_bundle?.name_en || 'Package Bundle' }}</h4>
                                    <p class="text-xs text-gray-400 font-mono">{{ booking.booking_number }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-semibold border capitalize" :class="bundleStatusColors[booking.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                    {{ booking.status?.replace('_', ' ') }}
                                </span>
                                <Link :href="`/secretary/bundle-bookings/${booking.id}`" class="text-teal-600 hover:text-teal-800 text-xs font-semibold transition-colors">{{ isRtl ? 'عرض' : 'View' }}</Link>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="px-3 sm:px-4 pt-3 pb-1">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-gray-500">{{ isRtl ? 'التقدم' : 'Progress' }}</span>
                                <span class="font-semibold text-teal-600">{{ bundleProgress(booking) }}%</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-teal-500 transition-all duration-500" :style="{ width: bundleProgress(booking) + '%' }"></div>
                            </div>
                        </div>

                        <!-- Financial Row -->
                        <div class="grid grid-cols-3 gap-3 sm:gap-4 px-3 sm:px-4 py-3 text-sm">
                            <div>
                                <span class="text-gray-400 text-xs block">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                                <span class="font-semibold text-gray-800">{{ formatCurrency(booking.total_price) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs block">{{ isRtl ? 'المدفوع' : 'Paid' }}</span>
                                <span class="font-semibold text-emerald-700">{{ formatCurrency(booking.total_paid) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs block">{{ isRtl ? 'المتبقي' : 'Remaining' }}</span>
                                <span class="font-semibold" :class="Number(booking.balance_due) > 0 ? 'text-red-600' : 'text-gray-500'">{{ formatCurrency(booking.balance_due) }}</span>
                            </div>
                        </div>

                        <!-- Services Breakdown -->
                        <div class="border-t border-gray-100">
                            <div class="px-3 sm:px-4 py-2.5 bg-gray-50/50">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الخدمات' : 'Services' }}</span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <div v-for="bs in booking.bundle_services" :key="bs.id" class="px-3 sm:px-4 py-3 flex flex-wrap items-center justify-between gap-3 sm:gap-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ bs.service?.name_en || '-' }}</p>
                                        <p class="text-xs text-gray-400">
                                            Dr. {{ bs.doctor?.name_en || '-' }}
                                            <span class="mx-1">&middot;</span>
                                            {{ formatCurrency(bs.bundle_price) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 flex-shrink-0">
                                        <!-- Sessions Counter -->
                                        <div class="flex items-center gap-1.5">
                                            <div class="flex gap-0.5">
                                                <span
                                                    v-for="n in (bs.sessions_count || 1)"
                                                    :key="n"
                                                    class="w-2.5 h-2.5 rounded-full border"
                                                    :class="n <= (bs.completed_sessions || 0)
                                                        ? 'bg-teal-500 border-teal-500'
                                                        : 'bg-white border-gray-300'"
                                                ></span>
                                            </div>
                                            <span class="text-xs text-gray-500 font-medium">{{ bs.completed_sessions || 0 }}/{{ bs.sessions_count || 0 }}</span>
                                        </div>
                                        <!-- Status Badge -->
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border capitalize" :class="bundleServiceStatusColors[bs.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                            {{ bs.status?.replace('_', ' ') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dates Footer -->
                        <div class="px-3 sm:px-4 py-2.5 bg-gray-50/50 border-t border-gray-100 flex flex-wrap items-center gap-3 sm:gap-6 text-xs text-gray-400">
                            <span v-if="booking.started_at">{{ isRtl ? 'بدأ:' : 'Started:' }} {{ formatDate(booking.started_at) }}</span>
                            <span v-if="booking.completed_at">{{ isRtl ? 'اكتمل:' : 'Completed:' }} {{ formatDate(booking.completed_at) }}</span>
                            <span v-if="!booking.started_at && !booking.completed_at">{{ isRtl ? 'أنشئ:' : 'Created:' }} {{ formatDate(booking.created_at) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-12 text-center">
                    <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد باقات بعد' : 'No package bundles yet' }}</p>
                </div>
            </div>

            <!-- Invoices Tab -->
            <div v-if="activeTab === 'invoices'" class="p-4 sm:p-6">
                <div v-if="patient.invoices?.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 rounded-lg">
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'فاتورة #' : 'Invoice #' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'المدفوع' : 'Paid' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                <th class="ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="invoice in patient.invoices" :key="invoice.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 font-mono font-semibold text-teal-600">{{ invoice.invoice_number || `#${invoice.id}` }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatDate(invoice.created_at) }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ formatCurrency(invoice.total_amount) }}</td>
                                <td class="px-4 py-3 text-emerald-600 font-medium hidden sm:table-cell">{{ formatCurrency(invoice.paid_amount) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border capitalize" :class="invoiceStatusColors[invoice.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 ltr:text-right rtl:text-left">
                                    <Link :href="`/secretary/invoices/${invoice.id}`" class="text-teal-600 hover:text-teal-800 text-xs font-semibold transition-colors">{{ isRtl ? 'عرض' : 'View' }}</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="py-12 text-center">
                    <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                    </div>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد فواتير' : 'No invoices found' }}</p>
                </div>
            </div>

            <!-- Prescriptions Tab -->
            <div v-if="activeTab === 'prescriptions'" class="p-4 sm:p-6">
                <div v-if="patient.prescriptions?.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 rounded-lg">
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الأدوية' : 'Medications' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="rx in patient.prescriptions" :key="rx.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 text-gray-600">{{ formatDate(rx.created_at) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ rx.doctor?.name_en || getDoctorName(rx.doctor_id) }}</td>
                                <td class="px-4 py-3 text-gray-800 hidden sm:table-cell">{{ rx.diagnosis || '-' }}</td>
                                <td class="px-4 py-3">
                                    <div v-if="rx.items?.length > 0" class="space-y-1">
                                        <div v-for="item in rx.items" :key="item.id" class="flex items-center gap-1.5 text-xs">
                                            <span class="w-1 h-1 rounded-full bg-teal-400 flex-shrink-0"></span>
                                            <span class="font-medium text-gray-700">{{ item.medication_name }}</span>
                                            <span class="text-gray-400">{{ [item.dosage, item.frequency, item.duration].filter(Boolean).join(' - ') }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="py-12 text-center">
                    <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" /></svg>
                    </div>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد وصفات طبية' : 'No prescriptions found' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
