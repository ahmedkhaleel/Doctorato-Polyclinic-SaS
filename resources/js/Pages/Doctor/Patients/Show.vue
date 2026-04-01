<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ patient: Object, dentalData: Object });

const statusConfig = {
    waiting: { label: 'Waiting', bg: 'bg-amber-100', text: 'text-amber-700' },
    in_progress: { label: 'In Progress', bg: 'bg-blue-100', text: 'text-blue-700' },
    completed: { label: 'Completed', bg: 'bg-emerald-100', text: 'text-emerald-700' },
    cancelled: { label: 'Cancelled', bg: 'bg-gray-100', text: 'text-gray-600' },
};

// ─── Doctor Notes on Patient ─────────────────────
const editingNotes = ref(false);
const notesForm = useForm({ doctor_notes: props.patient.doctor_notes || '' });
function saveNotes() {
    notesForm.put(`/doctor/patients/${props.patient.id}/notes`, {
        preserveScroll: true,
        onSuccess: () => { editingNotes.value = false; },
    });
}

// Expanded visit tracking
const expandedVisit = ref(null);
function toggleVisit(id) {
    expandedVisit.value = expandedVisit.value === id ? null : id;
}

// Collect all photos across visits for timeline
const allPhotos = computed(() => {
    const photos = [];
    for (const visit of (props.patient.visits || [])) {
        for (const photo of (visit.photos || [])) {
            photos.push({
                ...photo,
                visit_date: visit.visit_date,
                visit_id: visit.id,
                service_name: visit.service?.name_en || visit.visit_type,
            });
        }
    }
    return photos;
});

const beforePhotos = computed(() => allPhotos.value.filter(p => p.type === 'before'));
const afterPhotos = computed(() => allPhotos.value.filter(p => p.type === 'after'));

const hasDentalVisits = computed(() => (props.patient?.visits || []).some(v => v.module === 'dental'));

// Stats
const visitStats = computed(() => {
    const visits = props.patient.visits || [];
    return {
        total: visits.length,
        completed: visits.filter(v => v.status === 'completed').length,
        prescriptions: visits.reduce((sum, v) => sum + (v.prescriptions?.length || 0), 0),
        photos: allPhotos.value.length,
    };
});
</script>

<template>
    <div>
        <!-- Back + Header -->
        <div class="mb-6">
            <Link href="/doctor/patients" class="text-xs text-[#C4A265] hover:underline mb-2 inline-block">{{ isRtl ? 'العودة للمرضى ←' : '← Back to Patients' }}</Link>
            <h1 class="text-2xl font-bold text-gray-900">{{ patient.full_name }}</h1>
            <div class="flex items-center gap-4 mt-1">
                <span v-if="patient.file_number" class="text-sm font-mono text-[#C4A265]">{{ patient.file_number }}</span>
                <span v-if="patient.phone" class="text-sm text-gray-500">{{ patient.phone }}</span>
                <span v-if="patient.age" class="text-sm text-gray-400">{{ patient.age }} {{ isRtl ? 'سنة' : 'years' }}</span>
            </div>
        </div>

        <!-- Allergy Warning Banner -->
        <div v-if="patient.allergies" class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-red-700">{{ isRtl ? 'تنبيه حساسية' : 'Allergy Alert' }}</h3>
                <p class="text-sm text-red-600 mt-0.5">{{ patient.allergies }}</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <p class="text-xs text-gray-500 font-medium">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ visitStats.total }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <p class="text-xs text-emerald-600 font-medium">{{ isRtl ? 'مكتملة' : 'Completed' }}</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ visitStats.completed }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <p class="text-xs text-[#C4A265] font-medium">{{ $t('a_prescriptions') }}</p>
                <p class="text-2xl font-bold text-[#C4A265] mt-1">{{ visitStats.prescriptions }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <p class="text-xs text-blue-600 font-medium">{{ isRtl ? 'صور' : 'Photos' }}</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ visitStats.photos }}</p>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="grid lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'بيانات المريض' : 'Patient Details' }}</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الجنس' : 'Gender' }}</span><span class="font-medium text-gray-800 capitalize">{{ patient.gender || '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }}</span><span class="font-medium text-gray-800">{{ patient.date_of_birth || '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'فصيلة الدم' : 'Blood Type' }}</span><span class="font-medium text-gray-800">{{ patient.blood_type || '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'البريد الإلكتروني' : 'Email' }}</span><span class="font-medium text-gray-800">{{ patient.email || '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'العنوان' : 'Address' }}</span><span class="font-medium text-gray-800 ltr:text-right rtl:text-left max-w-[180px]">{{ patient.address || '-' }}</span></div>
                </div>
            </div>
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'التاريخ الطبي' : 'Medical History' }}</h3>
                <p v-if="patient.medical_history" class="text-sm text-gray-700 whitespace-pre-wrap">{{ patient.medical_history }}</p>
                <p v-else class="text-sm text-gray-400 italic">{{ isRtl ? 'لا يوجد تاريخ طبي مسجل' : 'No medical history recorded' }}</p>
            </div>
        </div>

        <!-- Doctor Notes (editable by the doctor) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold text-gray-500 uppercase flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    {{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}
                </h3>
                <button v-if="!editingNotes" @click="editingNotes = true" class="text-xs text-[#C4A265] hover:underline font-medium">
                    {{ isRtl ? 'تعديل' : 'Edit' }}
                </button>
            </div>
            <div v-if="editingNotes">
                <textarea v-model="notesForm.doctor_notes" rows="4" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] resize-none" :placeholder="isRtl ? 'أضف ملاحظاتك عن هذا المريض...' : 'Add your notes about this patient...'" />
                <div class="flex items-center gap-2 mt-2">
                    <button @click="saveNotes" :disabled="notesForm.processing" class="px-4 py-1.5 bg-[#C4A265] text-white text-xs font-bold rounded-lg hover:bg-[#B08D4C] transition disabled:opacity-50">
                        {{ notesForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save') }}
                    </button>
                    <button @click="editingNotes = false; notesForm.doctor_notes = patient.doctor_notes || ''" class="px-4 py-1.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-200 transition">
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </button>
                </div>
            </div>
            <div v-else>
                <p v-if="patient.doctor_notes" class="text-sm text-gray-700 whitespace-pre-wrap">{{ patient.doctor_notes }}</p>
                <p v-else class="text-sm text-gray-400 italic">{{ isRtl ? 'لا توجد ملاحظات بعد — اضغط تعديل لإضافة ملاحظاتك' : 'No notes yet — click Edit to add your notes' }}</p>
            </div>
        </div>

        <!-- Photo Timeline (Before/After) -->
        <div v-if="allPhotos.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6 mb-6">
            <h2 class="text-base font-bold text-gray-800 mb-4">{{ isRtl ? 'الصور الزمنية' : 'Photo Timeline' }}</h2>

            <div v-if="beforePhotos.length > 0 || afterPhotos.length > 0" class="grid md:grid-cols-2 gap-6 mb-4">
                <!-- Before -->
                <div v-if="beforePhotos.length > 0">
                    <p class="text-xs font-semibold text-amber-600 uppercase mb-2">Before ({{ beforePhotos.length }})</p>
                    <div class="grid grid-cols-3 gap-2">
                        <div v-for="photo in beforePhotos" :key="photo.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group">
                            <img :src="'/storage/' + photo.photo_path" :alt="photo.caption || 'Before'" class="w-full h-full object-cover" />
                            <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                {{ photo.visit_date }} · {{ photo.service_name }}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- After -->
                <div v-if="afterPhotos.length > 0">
                    <p class="text-xs font-semibold text-emerald-600 uppercase mb-2">After ({{ afterPhotos.length }})</p>
                    <div class="grid grid-cols-3 gap-2">
                        <div v-for="photo in afterPhotos" :key="photo.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group">
                            <img :src="'/storage/' + photo.photo_path" :alt="photo.caption || 'After'" class="w-full h-full object-cover" />
                            <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                {{ photo.visit_date }} · {{ photo.service_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dental Section (only if module enabled) -->
        <div v-if="$page.props.modules?.dental?.enabled && dentalData" class="space-y-4 mb-6">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                {{ isRtl ? 'طب الأسنان' : 'Dental' }}
            </h2>

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
                <Link :href="`/doctor/dental/chart/${patient.id}`"
                    class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-[#C4A265]/40 hover:bg-[#C4A265]/5 transition group shadow-sm"
                >
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center border border-[#C4A265]/20" style="background: rgba(196,162,101,0.08);">
                        <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-[#C4A265]">{{ isRtl ? 'المخطط' : 'Chart' }}</p>
                        <p class="text-[10px] text-gray-400">{{ dentalData?.charts?.length || 0 }} {{ isRtl ? 'سن مسجل' : 'teeth recorded' }}</p>
                    </div>
                </Link>
                <Link :href="`/doctor/dental/treatment-plans?patient_id=${patient.id}`"
                    class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-purple-300 hover:bg-purple-50/50 transition group shadow-sm"
                >
                    <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-purple-600">{{ isRtl ? 'الخطط' : 'Plans' }}</p>
                        <p class="text-[10px] text-gray-400">{{ dentalData?.plans?.length || 0 }} {{ isRtl ? 'خطة' : 'plans' }}</p>
                    </div>
                </Link>
                <Link :href="`/doctor/dental/treatments?patient_id=${patient.id}`"
                    class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 transition group shadow-sm"
                >
                    <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-teal-600">{{ isRtl ? 'العلاجات' : 'Treatments' }}</p>
                        <p class="text-[10px] text-gray-400">{{ dentalData?.treatments?.length || 0 }} {{ isRtl ? 'أخيرة' : 'recent' }}</p>
                    </div>
                </Link>
                <Link :href="`/doctor/dental/xrays/${patient.id}`"
                    class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-cyan-300 hover:bg-cyan-50/50 transition group shadow-sm"
                >
                    <div class="w-9 h-9 rounded-lg bg-cyan-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800 group-hover:text-cyan-600">{{ isRtl ? 'الأشعة' : 'X-Rays' }}</p>
                        <p class="text-[10px] text-gray-400">{{ dentalData?.xrays?.length || 0 }} {{ isRtl ? 'صورة' : 'images' }}</p>
                    </div>
                </Link>
            </div>

            <!-- Recent Treatments -->
            <div v-if="dentalData?.treatments?.length" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'آخر العلاجات' : 'Recent Treatments' }}</h3>
                    <span class="text-xs text-gray-400">{{ dentalData.treatments.length }} {{ isRtl ? 'من' : 'of' }} {{ dentalData.stats?.total_treatments }}</span>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="t in dentalData.treatments" :key="t.id" class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50/50">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                :class="t.status === 'completed' ? 'bg-green-50 text-green-600' : t.status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-500'">
                                {{ t.tooth_number || '—' }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ t.treatment_type?.replace(/_/g, ' ') }}</p>
                                <p class="text-[10px] text-gray-400">
                                    <span v-if="t.created_at">{{ t.created_at?.substring(0, 10) }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span v-if="t.lab_order" class="px-1.5 py-0.5 text-[9px] font-semibold rounded bg-amber-50 text-amber-600 border border-amber-200">
                                {{ isRtl ? 'معمل' : 'Lab' }}: {{ t.lab_order.status }}
                            </span>
                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize"
                                :class="t.status === 'completed' ? 'bg-green-100 text-green-700' : t.status === 'in_progress' ? 'bg-blue-100 text-blue-700' : t.status === 'planned' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-700'">
                                {{ t.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Treatment Plans -->
            <div v-if="dentalData?.plans?.length" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}</h3>
                </div>
                <div class="divide-y divide-gray-50">
                    <Link v-for="plan in dentalData.plans" :key="plan.id"
                        :href="`/doctor/dental/treatment-plans/${plan.id}`"
                        class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-purple-50/30 transition group"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 group-hover:text-purple-600 truncate">{{ plan.title_en || plan.title_ar || (isRtl ? 'خطة علاج' : 'Treatment Plan') }}</p>
                            <p class="text-[10px] text-gray-400">
                                {{ plan.treatments_count }} {{ isRtl ? 'علاج' : 'treatments' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <div v-if="plan.estimated_sessions > 0" class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" :style="{ width: Math.min(100, Math.round((plan.completed_sessions / plan.estimated_sessions) * 100)) + '%' }"></div>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize"
                                :class="plan.status === 'completed' ? 'bg-green-100 text-green-700' : plan.status === 'approved' ? 'bg-blue-100 text-blue-700' : plan.status === 'in_progress' ? 'bg-cyan-100 text-cyan-700' : plan.status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                                {{ plan.status }}
                            </span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Dental Visits -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'زيارات الأسنان' : 'Dental Visits' }}</h3>
                <div class="space-y-2">
                    <Link v-for="visit in patient.visits?.filter(v => v.module === 'dental')" :key="'dv-' + visit.id"
                        :href="`/doctor/visits/${visit.id}`"
                        class="flex items-center justify-between p-4 rounded-lg bg-white border border-gray-200 hover:border-[#C4A265]/40 hover:bg-[#C4A265]/5 transition group shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white" style="background-color: #C4A265;">
                                {{ visit.visit_date?.substring(8, 10) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 group-hover:text-[#C4A265]">
                                    {{ visit.service?.name_en || visit.visit_type }}
                                </p>
                                <p class="text-xs text-gray-500">{{ visit.visit_date }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize" :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text]">{{ statusConfig[visit.status]?.label || visit.status }}</span>
                    </Link>
                    <p v-if="!patient.visits?.filter(v => v.module === 'dental')?.length" class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا توجد زيارات أسنان' : 'No dental visits yet' }}</p>
                </div>
            </div>
        </div>

        <!-- Visit History -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'سجل الزيارات' : 'Visit History' }}</h2>
            </div>
            <div v-if="patient.visits?.length > 0" class="divide-y divide-gray-100">
                <div v-for="visit in patient.visits" :key="visit.id">
                    <!-- Visit Row -->
                    <div class="flex items-center justify-between px-6 py-3 hover:bg-gray-50/50 cursor-pointer" @click="toggleVisit(visit.id)">
                        <div class="flex items-center gap-3 flex-1">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-800">{{ visit.service?.name_en || visit.visit_type }}</p>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text]">
                                        {{ statusConfig[visit.status]?.label }}
                                    </span>
                                    <span v-if="visit.prescriptions?.length" class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-[#C4A265]/10 text-[#C4A265]">
                                        {{ visit.prescriptions.length }} Rx
                                    </span>
                                    <span v-if="visit.photos?.length" class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-500">
                                        {{ visit.photos.length }} photos
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">{{ visit.visit_date }} &middot; {{ visit.visit_type }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link :href="`/doctor/visits/${visit.id}`" class="text-xs font-medium text-[#C4A265] hover:underline" @click.stop>{{ isRtl ? 'عرض' : 'View' }}</Link>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedVisit === visit.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>

                    <!-- Expanded Details -->
                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <div v-if="expandedVisit === visit.id" class="px-6 pb-4">
                            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                                <!-- Diagnosis -->
                                <div v-if="visit.diagnosis">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</p>
                                    <p class="text-sm text-gray-700">{{ visit.diagnosis }}</p>
                                </div>
                                <div v-if="visit.doctor_notes">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</p>
                                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ visit.doctor_notes }}</p>
                                </div>

                                <!-- Prescriptions -->
                                <div v-if="visit.prescriptions?.length > 0">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $t('a_prescriptions') }}</p>
                                    <div v-for="rx in visit.prescriptions" :key="rx.id" class="bg-white rounded-lg p-3 mb-2 border border-gray-200">
                                        <p v-if="rx.diagnosis" class="text-xs text-gray-500 mb-1.5">Dx: {{ rx.diagnosis }}</p>
                                        <div v-for="item in rx.items" :key="item.id" class="flex items-center gap-2 text-xs py-0.5">
                                            <span class="w-1 h-1 rounded-full bg-[#C4A265]"></span>
                                            <span class="font-medium text-gray-800">{{ item.medication_name }}</span>
                                            <span class="text-gray-400">{{ [item.dosage, item.frequency, item.duration].filter(Boolean).join(' · ') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Photos -->
                                <div v-if="visit.photos?.length > 0">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ isRtl ? 'صور' : 'Photos' }}</p>
                                    <div class="flex gap-2 overflow-x-auto pb-1">
                                        <div v-for="photo in visit.photos" :key="photo.id" class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-gray-100 relative">
                                            <img :src="'/storage/' + photo.photo_path" :alt="photo.caption" class="w-full h-full object-cover" />
                                            <span class="absolute top-1 left-1 text-[8px] font-bold px-1 py-0.5 rounded bg-black/50 text-white uppercase">{{ photo.type }}</span>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="!visit.diagnosis && !visit.doctor_notes && !visit.prescriptions?.length && !visit.photos?.length" class="text-xs text-gray-400 italic">{{ isRtl ? 'لا توجد تفاصيل إضافية مسجلة' : 'No additional details recorded' }}</p>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
            <div v-else class="py-12 text-center">
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد زيارات مسجلة' : 'No visits recorded' }}</p>
            </div>
        </div>
    </div>
</template>
