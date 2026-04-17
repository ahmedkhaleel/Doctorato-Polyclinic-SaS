<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    visit: Object,
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

const statusColors = {
    scheduled: 'bg-slate-100 text-[#1B365D]',
    in_progress: 'bg-yellow-100 text-amber-700',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-red-100 text-red-700',
    no_show: 'bg-gray-100 text-gray-500',
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('/visits')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'تفاصيل الزيارة' : 'Visit Details' }}</h1>
        </div>

        <!-- Visit Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'التاريخ' : 'Date' }}</span>
                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ visit?.visit_date }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الطبيب' : 'Doctor' }}</span>
                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $localized(visit?.doctor, 'name') }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الخدمة' : 'Service' }}</span>
                    <p class="text-sm font-semibold text-gray-800 mt-0.5 flex items-center gap-1.5">
                        <svg v-if="visit?.module === 'dental'" class="w-3.5 h-3.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                        {{ $localized(visit?.service, 'name') || $localized(visit, 'service_name') }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</span>
                    <div class="mt-1">
                        <span :class="statusColors[visit?.status] || 'bg-gray-100 text-gray-500'" class="text-xs font-medium px-2.5 py-1 rounded-full">{{ visit?.status }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Diagnosis -->
            <div v-if="visit?.diagnosis" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</h2>
                <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ visit.diagnosis }}</p>
            </div>

            <!-- Doctor Notes -->
            <div v-if="visit?.doctor_notes || visit?.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</h2>
                <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ visit.doctor_notes || visit.notes }}</p>
            </div>
        </div>

        <!-- Dental Treatments (for dental visits) -->
        <div v-if="visit?.dental_treatments?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                {{ isRtl ? 'علاجات الأسنان' : 'Dental Treatments' }}
            </h2>
            <div class="space-y-3">
                <div v-for="dt in visit.dental_treatments" :key="dt.id" class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                            :class="dt.status === 'completed' ? 'bg-emerald-50 text-emerald-600' : dt.status === 'in_progress' ? 'bg-slate-50 text-[#1B365D]' : 'bg-gray-100 text-gray-500'">
                            {{ dt.tooth_number || '—' }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ dt.treatment_type?.replace(/_/g, ' ') }}</p>
                            <p v-if="dt.notes" class="text-xs text-gray-400 mt-0.5 truncate">{{ dt.notes }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span v-if="dt.lab_order" class="px-1.5 py-0.5 text-[9px] font-semibold rounded bg-amber-50 text-amber-600 border border-amber-200">
                            {{ isRtl ? 'معمل' : 'Lab' }}: {{ dt.lab_order.status }}
                        </span>
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize"
                            :class="dt.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : dt.status === 'in_progress' ? 'bg-slate-100 text-[#1B365D]' : 'bg-gray-100 text-gray-500'">
                            {{ dt.status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photos Section -->
        <div v-if="visit?.photos?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ isRtl ? 'الصور' : 'Photos' }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <div v-for="photo in visit.photos" :key="photo.id" class="relative group">
                    <img
                        :src="photo.photo_url || photo.url || ('/storage/' + photo.photo_path)"
                        :alt="photo.type || 'Visit photo'"
                        class="w-full h-40 object-cover rounded-xl border border-gray-100"
                    />
                    <span v-if="photo.type" class="absolute top-2 ltr:left-2 rtl:right-2 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-black/60 text-white">
                        {{ photo.type }}
                    </span>
                    <span v-if="photo.label" class="absolute bottom-2 ltr:left-2 rtl:right-2 text-[10px] font-medium px-2 py-0.5 rounded-full bg-white/90 text-gray-700">
                        {{ photo.label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Linked Prescription -->
        <div v-if="visit?.prescription" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">{{ isRtl ? 'الوصفة الطبية' : 'Prescription' }}</h2>
                <Link :href="lp('/prescriptions/' + visit.prescription.id)" class="text-sm text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] font-medium">
                    {{ isRtl ? 'عرض التفاصيل' : 'View Details' }}
                </Link>
            </div>
            <div v-if="visit.prescription.medications?.length" class="space-y-2">
                <div v-for="med in visit.prescription.medications" :key="med.id" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 text-sm">
                    <svg class="w-4 h-4 text-[var(--brand-primary)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" /></svg>
                    <span class="text-gray-700">{{ $localized(med, 'medication_name') || med.medication_name }}</span>
                    <span class="text-gray-400 text-xs">{{ med.quantity }} &middot; {{ med.frequency }}</span>
                </div>
            </div>
        </div>

        <!-- Linked Invoice -->
        <div v-if="visit?.invoice" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">{{ isRtl ? 'الفاتورة' : 'Invoice' }}</h2>
                <Link :href="lp('/invoices/' + visit.invoice.id)" class="text-sm text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] font-medium">
                    {{ isRtl ? 'عرض التفاصيل' : 'View Details' }}
                </Link>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-400 text-xs">{{ isRtl ? 'رقم الفاتورة' : 'Invoice #' }}</span>
                    <p class="font-medium text-gray-800">{{ visit.invoice.invoice_number }}</p>
                </div>
                <div>
                    <span class="text-gray-400 text-xs">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                    <p class="font-medium text-gray-800">{{ formatCurrency(visit.invoice.total) }}</p>
                </div>
                <div>
                    <span class="text-gray-400 text-xs">{{ isRtl ? 'المدفوع' : 'Paid' }}</span>
                    <p class="font-medium text-emerald-600">{{ formatCurrency(visit.invoice.paid_amount) }}</p>
                </div>
                <div>
                    <span class="text-gray-400 text-xs">{{ isRtl ? 'المتبقي' : 'Balance' }}</span>
                    <p class="font-medium" :class="(visit.invoice.total - visit.invoice.paid_amount) > 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ formatCurrency(visit.invoice.total - visit.invoice.paid_amount) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
