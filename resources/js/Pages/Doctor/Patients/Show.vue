<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ patient: Object, dentalData: Object });

const headerLoaded = ref(false);
const cardsLoaded = ref(false);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

const statusConfig = {
    waiting: { label: 'Waiting', labelAr: 'انتظار', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-400' },
    in_progress: { label: 'In Progress', labelAr: 'قيد التنفيذ', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
    completed: { label: 'Completed', labelAr: 'مكتمل', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    cancelled: { label: 'Cancelled', labelAr: 'ملغي', bg: 'bg-gray-50', text: 'text-gray-600', dot: 'bg-gray-400' },
};

// Doctor Notes
const editingNotes = ref(false);
const notesForm = useForm({ doctor_notes: props.patient.doctor_notes || '' });
function saveNotes() {
    notesForm.put(`/doctor/patients/${props.patient.id}/notes`, {
        preserveScroll: true,
        onSuccess: () => { editingNotes.value = false; },
    });
}

const expandedVisit = ref(null);
function toggleVisit(id) { expandedVisit.value = expandedVisit.value === id ? null : id; }

const allPhotos = computed(() => {
    const photos = [];
    for (const visit of (props.patient.visits || [])) {
        for (const photo of (visit.photos || [])) {
            photos.push({ ...photo, visit_date: visit.visit_date, visit_id: visit.id, service_name: visit.service?.name_en || visit.visit_type });
        }
    }
    return photos;
});

const beforePhotos = computed(() => allPhotos.value.filter(p => p.type === 'before'));
const afterPhotos = computed(() => allPhotos.value.filter(p => p.type === 'after'));
const hasDentalVisits = computed(() => (props.patient?.visits || []).some(v => v.module === 'dental'));

const visitStats = computed(() => {
    const visits = props.patient.visits || [];
    return {
        total: visits.length,
        completed: visits.filter(v => v.status === 'completed').length,
        prescriptions: visits.reduce((sum, v) => sum + (v.prescriptions?.length || 0), 0),
        photos: allPhotos.value.length,
    };
});

const activeTab = ref('visits');
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
                <!-- Back Link -->
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
                        <p class="text-xs text-gray-400">{{ $t('a_prescriptions') }}</p>
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

        <!-- Tabs -->
        <div
            class="flex gap-1 bg-white rounded-xl p-1 shadow-sm border border-gray-100 transition-all duration-500"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <button @click="activeTab = 'visits'" class="flex-1 px-3 py-2.5 rounded-lg text-sm font-medium transition-all" :class="activeTab === 'visits' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'">
                {{ isRtl ? 'الزيارات' : 'Visits' }}
            </button>
            <button @click="activeTab = 'info'" class="flex-1 px-3 py-2.5 rounded-lg text-sm font-medium transition-all" :class="activeTab === 'info' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'">
                {{ isRtl ? 'البيانات' : 'Details' }}
            </button>
            <button @click="activeTab = 'notes'" class="flex-1 px-3 py-2.5 rounded-lg text-sm font-medium transition-all" :class="activeTab === 'notes' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'">
                {{ isRtl ? 'ملاحظاتي' : 'My Notes' }}
            </button>
            <button v-if="allPhotos.length" @click="activeTab = 'photos'" class="flex-1 px-3 py-2.5 rounded-lg text-sm font-medium transition-all" :class="activeTab === 'photos' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'">
                {{ isRtl ? 'الصور' : 'Photos' }}
            </button>
            <button v-if="$page.props.modules?.dental?.enabled && dentalData" @click="activeTab = 'dental'" class="flex-1 px-3 py-2.5 rounded-lg text-sm font-medium transition-all" :class="activeTab === 'dental' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'">
                {{ isRtl ? 'الأسنان' : 'Dental' }}
            </button>
        </div>

        <!-- Tab Content -->
        <div class="transition-all duration-700" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <!-- Visits Tab -->
            <div v-if="activeTab === 'visits'" class="space-y-3">
                <div v-for="visit in (patient.visits || [])" :key="visit.id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden"
                >
                    <div class="flex items-center gap-4 p-4 cursor-pointer" @click="toggleVisit(visit.id)">
                        <!-- Date -->
                        <div class="w-12 h-12 rounded-xl bg-gray-50 flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold text-gray-800 leading-none">{{ visit.visit_date ? new Date(visit.visit_date).getDate() : '-' }}</span>
                            <span class="text-[9px] text-gray-400 font-medium mt-0.5">{{ visit.visit_date ? new Date(visit.visit_date).toLocaleDateString('en', { month: 'short' }) : '' }}</span>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ isRtl ? (visit.service?.name_ar || visit.service?.name_en) : (visit.service?.name_en || visit.service?.name_ar) || visit.visit_type || '-' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ visit.visit_date }}</p>
                        </div>

                        <!-- Status -->
                        <span v-if="statusConfig[visit.status]" class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1 rounded-full" :class="[statusConfig[visit.status].bg, statusConfig[visit.status].text]">
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[visit.status].dot"></span>
                            {{ isRtl ? statusConfig[visit.status].labelAr : statusConfig[visit.status].label }}
                        </span>

                        <!-- Expand -->
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="expandedVisit === visit.id ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>

                    <!-- Expanded Details -->
                    <div v-if="expandedVisit === visit.id" class="px-4 pb-4 border-t border-gray-100 pt-3 space-y-3">
                        <div v-if="visit.diagnosis" class="bg-blue-50 rounded-lg p-3">
                            <p class="text-[10px] font-semibold text-blue-500 uppercase mb-1">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</p>
                            <p class="text-sm text-blue-800">{{ visit.diagnosis }}</p>
                        </div>
                        <div v-if="visit.doctor_notes" class="bg-amber-50 rounded-lg p-3">
                            <p class="text-[10px] font-semibold text-amber-500 uppercase mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</p>
                            <p class="text-sm text-amber-800">{{ visit.doctor_notes }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link :href="`/doctor/visits/${visit.id}`" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#B08D4C] rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                {{ isRtl ? 'عرض الزيارة' : 'View Visit' }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="!(patient.visits?.length)" class="text-center py-12 bg-white rounded-xl border border-gray-100">
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد زيارات' : 'No visits yet' }}</p>
                </div>
            </div>

            <!-- Info Tab -->
            <div v-if="activeTab === 'info'" class="grid md:grid-cols-2 gap-5">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        {{ isRtl ? 'بيانات المريض' : 'Patient Details' }}
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">{{ isRtl ? 'الجنس' : 'Gender' }}</span><span class="font-medium text-gray-800 capitalize">{{ patient.gender || '-' }}</span></div>
                        <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }}</span><span class="font-medium text-gray-800">{{ patient.date_of_birth || '-' }}</span></div>
                        <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">{{ isRtl ? 'فصيلة الدم' : 'Blood Type' }}</span><span class="font-medium text-gray-800">{{ patient.blood_type || '-' }}</span></div>
                        <div class="flex justify-between py-1.5 border-b border-gray-50"><span class="text-gray-500">{{ $t('a_email') }}</span><span class="font-medium text-gray-800">{{ patient.email || '-' }}</span></div>
                        <div class="flex justify-between py-1.5"><span class="text-gray-500">{{ isRtl ? 'العنوان' : 'Address' }}</span><span class="font-medium text-gray-800 max-w-[180px] ltr:text-right rtl:text-left">{{ patient.address || '-' }}</span></div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        {{ isRtl ? 'التاريخ الطبي' : 'Medical History' }}
                    </h3>
                    <p v-if="patient.medical_history" class="text-sm text-gray-700 whitespace-pre-wrap">{{ patient.medical_history }}</p>
                    <p v-else class="text-sm text-gray-400 italic text-center py-4">{{ isRtl ? 'لا يوجد تاريخ طبي مسجل' : 'No medical history recorded' }}</p>
                </div>
            </div>

            <!-- Notes Tab -->
            <div v-if="activeTab === 'notes'">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
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
                        <textarea v-model="notesForm.doctor_notes" rows="5" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] resize-none transition" :placeholder="isRtl ? 'أضف ملاحظاتك عن هذا المريض...' : 'Add your notes about this patient...'" />
                        <div class="flex items-center gap-2 mt-3">
                            <button @click="saveNotes" :disabled="notesForm.processing" class="px-5 py-2 bg-[#C4A265] text-white text-sm font-semibold rounded-xl hover:bg-[#B08D4C] transition disabled:opacity-50">
                                {{ notesForm.processing ? $t('a_saving') : (isRtl ? 'حفظ' : 'Save') }}
                            </button>
                            <button @click="editingNotes = false; notesForm.doctor_notes = patient.doctor_notes || ''" class="px-5 py-2 bg-gray-100 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-200 transition">
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                        </div>
                    </div>
                    <div v-else>
                        <p v-if="patient.doctor_notes" class="text-sm text-gray-700 whitespace-pre-wrap bg-amber-50/50 rounded-xl p-4 border border-amber-100">{{ patient.doctor_notes }}</p>
                        <div v-else class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            <p class="text-sm text-gray-400 mt-2">{{ isRtl ? 'لا توجد ملاحظات — اضغط تعديل لإضافة ملاحظاتك' : 'No notes yet — click Edit to add' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photos Tab -->
            <div v-if="activeTab === 'photos' && allPhotos.length">
                <div class="grid md:grid-cols-2 gap-5">
                    <div v-if="beforePhotos.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-bold text-amber-600 uppercase mb-3 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-100 flex items-center justify-center text-xs">{{ beforePhotos.length }}</span>
                            {{ isRtl ? 'قبل' : 'Before' }}
                        </h3>
                        <div class="grid grid-cols-3 gap-2">
                            <div v-for="photo in beforePhotos" :key="photo.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group">
                                <img :src="'/storage/' + photo.photo_path" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2">
                                    <p class="text-white text-[9px]">{{ photo.visit_date }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="afterPhotos.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-bold text-emerald-600 uppercase mb-3 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center text-xs">{{ afterPhotos.length }}</span>
                            {{ isRtl ? 'بعد' : 'After' }}
                        </h3>
                        <div class="grid grid-cols-3 gap-2">
                            <div v-for="photo in afterPhotos" :key="photo.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group">
                                <img :src="'/storage/' + photo.photo_path" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2">
                                    <p class="text-white text-[9px]">{{ photo.visit_date }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dental Tab -->
            <div v-if="activeTab === 'dental' && $page.props.modules?.dental?.enabled && dentalData" class="space-y-5">
                <!-- Dental Stats -->
                <div v-if="dentalData?.stats" class="grid grid-cols-3 gap-3">
                    <div class="bg-white rounded-xl border border-cyan-100 p-4 shadow-sm">
                        <p class="text-2xl font-bold text-cyan-700">{{ dentalData.stats.total_treatments }}</p>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'إجمالي العلاجات' : 'Total Treatments' }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-green-100 p-4 shadow-sm">
                        <p class="text-2xl font-bold text-green-600">{{ dentalData.stats.completed_treatments }}</p>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-purple-100 p-4 shadow-sm">
                        <p class="text-2xl font-bold text-purple-600">{{ dentalData.stats.active_plans }}</p>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'خطط نشطة' : 'Active Plans' }}</p>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Link :href="`/doctor/dental/chart/${patient.id}`" class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-[#C4A265]/40 hover:bg-[#C4A265]/5 transition group shadow-sm">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-[#C4A265]/10"><svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg></div>
                        <div><p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'المخطط' : 'Chart' }}</p><p class="text-[10px] text-gray-400">{{ dentalData?.charts?.length || 0 }} {{ isRtl ? 'سن' : 'teeth' }}</p></div>
                    </Link>
                    <Link :href="`/doctor/dental/treatment-plans?patient_id=${patient.id}`" class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-purple-300 hover:bg-purple-50/50 transition group shadow-sm">
                        <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center"><svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg></div>
                        <div><p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'الخطط' : 'Plans' }}</p><p class="text-[10px] text-gray-400">{{ dentalData?.plans?.length || 0 }} {{ isRtl ? 'خطة' : 'plans' }}</p></div>
                    </Link>
                    <Link :href="`/doctor/dental/treatments?patient_id=${patient.id}`" class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 transition group shadow-sm">
                        <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center"><svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg></div>
                        <div><p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'العلاجات' : 'Treatments' }}</p><p class="text-[10px] text-gray-400">{{ dentalData?.treatments?.length || 0 }} {{ isRtl ? 'أخيرة' : 'recent' }}</p></div>
                    </Link>
                    <Link :href="`/doctor/dental/xrays/${patient.id}`" class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-cyan-300 hover:bg-cyan-50/50 transition group shadow-sm">
                        <div class="w-9 h-9 rounded-lg bg-cyan-50 flex items-center justify-center"><svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                        <div><p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'الأشعة' : 'X-Rays' }}</p><p class="text-[10px] text-gray-400">{{ dentalData?.xrays?.length || 0 }} {{ isRtl ? 'صورة' : 'images' }}</p></div>
                    </Link>
                </div>

                <!-- Recent Treatments -->
                <div v-if="dentalData?.treatments?.length" class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'آخر العلاجات' : 'Recent Treatments' }}</h3>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="t in dentalData.treatments" :key="t.id" class="px-5 py-3 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-cyan-50 flex items-center justify-center text-xs font-bold text-cyan-600">{{ t.tooth_number }}</div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ t.treatment_type }}</p>
                                <p class="text-xs text-gray-400">{{ t.created_at ? new Date(t.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : '' }}</p>
                            </div>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="t.status === 'completed' ? 'bg-emerald-50 text-emerald-600' : t.status === 'cancelled' ? 'bg-gray-50 text-gray-500' : 'bg-amber-50 text-amber-600'">{{ isRtl ? ({ completed: 'مكتمل', planned: 'مخطط', in_progress: 'جاري', cancelled: 'ملغي' }[t.status] || t.status) : t.status }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
