<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { getAgeDisplay } from '@/Composables/usePediatricAge';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    wellChildVisits: Array,
    schedule: Array,
    ageMonths: Number,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const ageDisplay = computed(() => getAgeDisplay(props.ageMonths, null));

// Merge schedule with existing visit records
const mergedSchedule = computed(() => {
    return (props.schedule || []).map(item => {
        const visit = (props.wellChildVisits || []).find(v => v.schedule_key === item.key);
        const isPast = props.patient?.date_of_birth && props.ageMonths
            ? item.age_months <= props.ageMonths
            : false;
        return {
            ...item,
            visit: visit || null,
            status: visit?.status || (isPast ? 'missed' : 'upcoming'),
            isNext: !visit && isPast === false,
        };
    });
});

const completedCount = computed(() => mergedSchedule.value.filter(s => s.visit?.status === 'completed').length);
const totalCount = computed(() => mergedSchedule.value.length);

// ── Edit Modal ──
const editingVisit = ref(null);
const showEditModal = ref(false);

const editForm = useForm({
    visit_date: '',
    status: 'completed',
    weight_kg: '',
    height_cm: '',
    head_circumference_cm: '',
    physical_exam_notes: '',
    development_notes: '',
    feeding_notes: '',
    safety_guidance: '',
    notes: '',
});

function openEdit(item) {
    editingVisit.value = item;
    if (item.visit) {
        editForm.visit_date = item.visit.visit_date || new Date().toISOString().split('T')[0];
        editForm.status = item.visit.status;
        editForm.weight_kg = item.visit.weight_kg || '';
        editForm.height_cm = item.visit.height_cm || '';
        editForm.head_circumference_cm = item.visit.head_circumference_cm || '';
        editForm.physical_exam_notes = item.visit.physical_exam_notes || '';
        editForm.development_notes = item.visit.development_notes || '';
        editForm.feeding_notes = item.visit.feeding_notes || '';
        editForm.safety_guidance = item.visit.safety_guidance || '';
        editForm.notes = item.visit.notes || '';
    } else {
        editForm.reset();
        editForm.visit_date = new Date().toISOString().split('T')[0];
        editForm.status = 'completed';
    }
    showEditModal.value = true;
}

function saveVisit() {
    if (!editingVisit.value?.visit?.id) return;
    editForm.post(`/doctor/pediatric/well-child/${props.patient.id}/${editingVisit.value.visit.id}/update`, {
        preserveScroll: true,
        onSuccess: () => { showEditModal.value = false; editingVisit.value = null; },
    });
}

function initializeSchedule() {
    router.post(`/doctor/pediatric/well-child/${props.patient.id}/initialize`, {}, {
        preserveScroll: true,
    });
}

// Status styling
function statusColor(status) {
    return {
        completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        scheduled: 'bg-slate-100 text-[#1B365D] dark:bg-[#1B365D]/30 dark:text-slate-400',
        missed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        skipped: 'bg-gray-100 text-gray-500 dark:bg-gray-700/30 dark:text-gray-400',
        upcoming: 'bg-gray-50 text-gray-400 dark:bg-gray-800 dark:text-gray-500',
    }[status] || 'bg-gray-50 text-gray-400';
}

function statusLabel(status) {
    const labels = {
        completed: { en: 'Completed', ar: 'مكتمل' },
        scheduled: { en: 'Scheduled', ar: 'مجدول' },
        missed: { en: 'Missed', ar: 'فائت' },
        skipped: { en: 'Skipped', ar: 'تم تخطيه' },
        upcoming: { en: 'Upcoming', ar: 'قادم' },
    };
    const l = labels[status] || labels.upcoming;
    return isRtl.value ? l.ar : l.en;
}

function dotColor(status) {
    return {
        completed: 'bg-emerald-500',
        scheduled: 'bg-[#1B365D]',
        missed: 'bg-red-500',
        skipped: 'bg-gray-400',
        upcoming: 'bg-gray-300 dark:bg-gray-600',
    }[status] || 'bg-gray-300';
}
</script>

<template>
    <div class="space-y-5">
        <!-- ═══════════ HEADER ═══════════ -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500 transition-all duration-700"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="relative z-10 px-5 py-5 sm:px-6 sm:py-6">
                <Link href="/doctor/pediatric/well-child" class="inline-flex items-center gap-1.5 text-xs text-white/80 hover:text-white transition mb-4">
                    <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'العودة لجدول الطفل السليم' : 'Back to Well-Child' }}
                </Link>

                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white text-xl font-bold ring-4 ring-white/20 shadow-lg"
                        :class="patient.gender === 'male' ? 'bg-[#1B365D]/40' : patient.gender === 'female' ? 'bg-[#C4A265]/40' : 'bg-white/20'">
                        {{ (patient.full_name || 'P').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">{{ patient.full_name }}</h1>
                        <div class="flex items-center gap-3 mt-1 text-xs text-white/80">
                            <span>{{ isRtl ? ageDisplay.ar : ageDisplay.en }}</span>
                            <span v-if="patient.gender" class="capitalize">{{ patient.gender === 'male' ? (isRtl ? 'ذكر' : 'Male') : (isRtl ? 'أنثى' : 'Female') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs text-white/80 mb-1.5">
                        <span>{{ isRtl ? 'تقدم الفحوصات' : 'Checkup Progress' }}</span>
                        <span class="font-bold text-white">{{ completedCount }} / {{ totalCount }}</span>
                    </div>
                    <div class="w-full bg-white/15 rounded-full h-3 overflow-hidden">
                        <div class="bg-white h-full rounded-full transition-all duration-1000 ease-out"
                            :style="{ width: totalCount ? ((completedCount / totalCount) * 100) + '%' : '0%' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Initialize Button (if no visits yet) -->
        <div v-if="!wellChildVisits?.length" class="text-center py-10 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ isRtl ? 'لم يتم تفعيل جدول الطفل السليم بعد' : 'Well-child schedule not initialized yet' }}</p>
            <button
                @click="initializeSchedule"
                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium rounded-xl transition shadow-sm shadow-emerald-500/20"
            >
                {{ isRtl ? 'تفعيل جدول الفحوصات' : 'Initialize Schedule' }}
            </button>
        </div>

        <!-- ═══════════ TIMELINE ═══════════ -->
        <div v-else class="space-y-0">
            <div
                v-for="(item, idx) in mergedSchedule"
                :key="item.key"
                class="relative transition-all duration-500"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                :style="{ transitionDelay: `${idx * 40}ms` }"
            >
                <!-- Timeline connector line -->
                <div v-if="idx < mergedSchedule.length - 1" class="absolute top-10 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"
                    :class="isRtl ? 'right-6' : 'left-6'"></div>

                <div class="flex items-start gap-4 pb-4">
                    <!-- Timeline dot -->
                    <div class="flex-shrink-0 relative z-10 w-12 flex flex-col items-center">
                        <div class="w-4 h-4 rounded-full ring-4 ring-white dark:ring-gray-900 transition-all duration-300"
                            :class="dotColor(item.status)">
                        </div>
                    </div>

                    <!-- Card -->
                    <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl border shadow-sm p-4 sm:p-5 transition-all duration-200 hover:shadow-md"
                        :class="item.status === 'completed' ? 'border-emerald-200 dark:border-emerald-800/50' : 'border-gray-100 dark:border-gray-700'">

                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
                                    {{ isRtl ? item.label_ar : item.label_en }}
                                </h3>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ isRtl ? 'العمر المتوقع:' : 'Expected age:' }}
                                    {{ item.age_months < 1 ? (isRtl ? 'أسبوع' : '1 week') : (item.age_months >= 12 ? Math.floor(item.age_months/12) + (isRtl ? ' سنة' : 'y') + (item.age_months % 12 ? ' ' + (item.age_months % 12) + (isRtl ? ' شهر' : 'm') : '') : item.age_months + (isRtl ? ' شهر' : 'm')) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full" :class="statusColor(item.status)">
                                    {{ statusLabel(item.status) }}
                                </span>
                                <button
                                    v-if="item.visit"
                                    @click="openEdit(item)"
                                    class="p-1.5 text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition"
                                    :title="isRtl ? 'تعديل' : 'Edit'"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Completed visit details -->
                        <div v-if="item.visit?.status === 'completed'" class="mt-3 pt-3 border-t border-gray-50 dark:border-gray-700/50">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div v-if="item.visit.visit_date">
                                    <span class="text-gray-400">{{ isRtl ? 'التاريخ' : 'Date' }}</span>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ item.visit.visit_date }}</p>
                                </div>
                                <div v-if="item.visit.weight_kg">
                                    <span class="text-gray-400">{{ isRtl ? 'الوزن' : 'Weight' }}</span>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ item.visit.weight_kg }} kg</p>
                                </div>
                                <div v-if="item.visit.height_cm">
                                    <span class="text-gray-400">{{ isRtl ? 'الطول' : 'Height' }}</span>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ item.visit.height_cm }} cm</p>
                                </div>
                                <div v-if="item.visit.head_circumference_cm">
                                    <span class="text-gray-400">{{ isRtl ? 'محيط الرأس' : 'Head' }}</span>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ item.visit.head_circumference_cm }} cm</p>
                                </div>
                            </div>
                            <p v-if="item.visit.notes" class="text-xs text-gray-500 dark:text-gray-400 mt-2 italic">{{ item.visit.notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════ EDIT MODAL ═══════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showEditModal = false">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
                        <!-- Modal Header -->
                        <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-100 dark:border-gray-700 rounded-t-2xl">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                    {{ isRtl ? 'تعديل فحص' : 'Edit Checkup' }}:
                                    {{ editingVisit ? (isRtl ? editingVisit.label_ar : editingVisit.label_en) : '' }}
                                </h3>
                                <button @click="showEditModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="saveVisit" class="px-6 py-4 space-y-4">
                            <!-- Status & Date Row -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'الحالة' : 'Status' }}</label>
                                    <select v-model="editForm.status" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition">
                                        <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                                        <option value="scheduled">{{ isRtl ? 'مجدول' : 'Scheduled' }}</option>
                                        <option value="missed">{{ isRtl ? 'فائت' : 'Missed' }}</option>
                                        <option value="skipped">{{ isRtl ? 'تم تخطيه' : 'Skipped' }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }}</label>
                                    <input v-model="editForm.visit_date" type="date" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition" />
                                </div>
                            </div>

                            <!-- Measurements -->
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'الوزن (كغ)' : 'Weight (kg)' }}</label>
                                    <input v-model="editForm.weight_kg" type="number" step="0.001" min="0" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'الطول (سم)' : 'Height (cm)' }}</label>
                                    <input v-model="editForm.height_cm" type="number" step="0.1" min="0" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'محيط الرأس' : 'Head (cm)' }}</label>
                                    <input v-model="editForm.head_circumference_cm" type="number" step="0.1" min="0" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition" />
                                </div>
                            </div>

                            <!-- Clinical Notes -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'ملاحظات الفحص البدني' : 'Physical Exam Notes' }}</label>
                                <textarea v-model="editForm.physical_exam_notes" rows="2" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition resize-none"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'ملاحظات التطور' : 'Development Notes' }}</label>
                                <textarea v-model="editForm.development_notes" rows="2" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition resize-none"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'ملاحظات التغذية' : 'Feeding Notes' }}</label>
                                    <textarea v-model="editForm.feeding_notes" rows="2" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition resize-none"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'إرشادات السلامة' : 'Safety Guidance' }}</label>
                                    <textarea v-model="editForm.safety_guidance" rows="2" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition resize-none"></textarea>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'ملاحظات عامة' : 'General Notes' }}</label>
                                <textarea v-model="editForm.notes" rows="2" class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition resize-none"></textarea>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-3 pt-2">
                                <button type="submit" :disabled="editForm.processing"
                                    class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition shadow-sm shadow-emerald-500/20">
                                    {{ editForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save') }}
                                </button>
                                <button type="button" @click="showEditModal = false"
                                    class="px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
