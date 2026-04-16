<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import SpecialtyTabs from '@/Components/Patient/SpecialtyTabs.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    activeSpecialties: { type: Array, default: () => [] },
    dermaData: { type: Object, default: null },
    dentalData: { type: Object, default: null },
    pediatricData: { type: Object, default: null },
    financialSummary: { type: Object, default: null },
    dentalRiskFlags: { type: Array, default: () => [] },
    dentalMedicalHistory: { type: Object, default: null },
    quickNotes: { type: Array, default: () => [] },
    isFavorite: { type: Boolean, default: false },
});

const headerLoaded = ref(false);
const cardsLoaded = ref(false);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

// Doctor Notes
const editingNotes = ref(false);
const notesForm = useForm({ doctor_notes: props.patient.doctor_notes || '' });
function saveNotes() {
    notesForm.put(`/doctor/patients/${props.patient.id}/notes`, {
        preserveScroll: true,
        onSuccess: () => { editingNotes.value = false; },
    });
}

const visits = computed(() => props.patient?.visits || []);
const invoices = computed(() => props.patient?.invoices || []);
const prescriptions = computed(() => props.patient?.prescriptions || []);

const visitStats = computed(() => {
    const all = visits.value;
    const photos = all.reduce((sum, v) => sum + (v.photos?.length || 0), 0);
    return {
        total: all.length,
        completed: all.filter(v => v.status === 'completed').length,
        prescriptions: all.reduce((sum, v) => sum + (v.prescriptions?.length || 0), 0),
        photos,
    };
});
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-[#C4A265]/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-purple-500/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10 p-6 sm:p-8">
                <Link href="/doctor/patients" class="inline-flex items-center gap-1.5 text-xs text-[#C4A265] hover:text-[#D4B87A] transition mb-4">
                    <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'العودة للمرضى' : 'Back to Patients' }}
                </Link>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <!-- Avatar -->
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center text-white text-2xl font-bold ring-4 ring-[#C4A265]/20 shadow-lg">
                        {{ patient.full_name?.charAt(0)?.toUpperCase() || 'P' }}
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-white">{{ patient.full_name }}</h1>
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            <span v-if="patient.file_number" class="inline-flex items-center gap-1 text-xs text-[#C4A265] font-mono bg-[#C4A265]/10 px-2.5 py-1 rounded-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" /></svg>
                                {{ patient.file_number }}
                            </span>
                            <span v-if="patient.phone" class="inline-flex items-center gap-1.5 text-xs text-white/50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                {{ patient.phone }}
                            </span>
                            <span v-if="patient.age" class="text-xs text-white/40">{{ patient.age }} {{ isRtl ? 'سنة' : 'years' }}</span>
                            <span v-if="patient.gender" class="text-xs text-white/40 capitalize">{{ patient.gender }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
                        <p class="text-xl font-bold text-white mt-0.5">{{ visitStats.total }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'مكتملة' : 'Completed' }}</p>
                        <p class="text-xl font-bold text-emerald-400 mt-0.5">{{ visitStats.completed }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'الوصفات' : 'Prescriptions' }}</p>
                        <p class="text-xl font-bold text-[#C4A265] mt-0.5">{{ visitStats.prescriptions }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'صور' : 'Photos' }}</p>
                        <p class="text-xl font-bold text-blue-400 mt-0.5">{{ visitStats.photos }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Allergy Warning -->
        <div v-if="patient.allergies"
            class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3 transition-all duration-500"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-red-700">{{ isRtl ? 'تنبيه حساسية' : 'Allergy Alert' }}</h3>
                <p class="text-sm text-red-600 mt-0.5">{{ patient.allergies }}</p>
            </div>
        </div>

        <!-- My Notes (doctor-only) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 transition-all duration-500"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    {{ isRtl ? 'ملاحظاتي عن المريض' : 'My Notes on Patient' }}
                </h3>
                <button v-if="!editingNotes" @click="editingNotes = true" class="text-xs font-medium text-[#C4A265] hover:text-[#B08D4C] transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    {{ isRtl ? 'تعديل' : 'Edit' }}
                </button>
            </div>
            <div v-if="editingNotes">
                <textarea v-model="notesForm.doctor_notes" rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] resize-none transition" :placeholder="isRtl ? 'أضف ملاحظاتك عن هذا المريض...' : 'Add your notes about this patient...'" />
                <div class="flex items-center gap-2 mt-3">
                    <button @click="saveNotes" :disabled="notesForm.processing" class="px-5 py-2 bg-[#C4A265] text-white text-sm font-semibold rounded-xl hover:bg-[#B08D4C] transition disabled:opacity-50">
                        {{ notesForm.processing ? (isRtl ? 'جار الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save') }}
                    </button>
                    <button @click="editingNotes = false; notesForm.doctor_notes = patient.doctor_notes || ''" class="px-5 py-2 bg-gray-100 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-200 transition">
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </button>
                </div>
            </div>
            <div v-else>
                <p v-if="patient.doctor_notes" class="text-sm text-gray-700 whitespace-pre-wrap bg-amber-50/50 rounded-xl p-3 border border-amber-100">{{ patient.doctor_notes }}</p>
                <p v-else class="text-sm text-gray-400 italic">{{ isRtl ? 'لا توجد ملاحظات — اضغط تعديل لإضافة ملاحظاتك' : 'No notes yet — click Edit to add' }}</p>
            </div>
        </div>

        <!-- Unified Specialty Tabs -->
        <div class="transition-all duration-700" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <SpecialtyTabs
                role="doctor"
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
    </div>
</template>
