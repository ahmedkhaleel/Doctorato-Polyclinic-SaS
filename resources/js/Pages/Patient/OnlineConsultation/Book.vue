<script setup>
import { computed, ref } from 'vue';
import { usePage, Link, useForm } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    doctor: Object,
    availability: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const flashError = computed(() => page.props.flash?.error || '');
function lp(path) { return `/${locale.value}/patient${path}`; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || '';
}

const selectedDate = ref(null);
const selectedSlot = ref(null);
const currentStep = ref(1);

const selectedDay = computed(() => {
    if (!selectedDate.value) return null;
    return props.availability.find(d => d.date === selectedDate.value);
});

function pickDay(day) {
    if (!day.has_available) return;
    selectedDate.value = day.date;
    selectedSlot.value = null;
    if (currentStep.value < 2) currentStep.value = 2;
}

function pickSlot(slot) {
    if (!slot.available) return;
    selectedSlot.value = slot;
    if (currentStep.value < 3) currentStep.value = 3;
}

const form = useForm({
    doctor_id: props.doctor.id,
    scheduled_date: '',
    start_time: '',
    end_time: '',
    chief_complaint: '',
});

function submit() {
    if (!selectedDate.value || !selectedSlot.value) return;
    form.scheduled_date = selectedDate.value;
    form.start_time = selectedSlot.value.start_time;
    form.end_time = selectedSlot.value.end_time;
    form.post(lp('/online-consultations'), { preserveScroll: true });
}

function doctorTypeLabel(type) {
    if (type === 'consultant') return isRtl.value ? 'استشاري' : 'Consultant';
    if (type === 'specialist') return isRtl.value ? 'أخصائي' : 'Specialist';
    return '';
}

const fee = computed(() => Number(props.doctor.online_consultation_fee || 0).toFixed(0));
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6">
        <!-- Doctor Info Sidebar -->
        <aside class="lg:sticky lg:top-[88px] h-fit">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-br from-[#1B365D] to-[#22406F] p-5 text-center">
                    <div class="w-20 h-20 mx-auto rounded-full p-[2px] bg-gradient-to-br from-[#C4A265] to-[#D9B985]">
                        <div class="w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden">
                            <img v-if="doctor.photo" :src="`/storage/${doctor.photo}`" class="w-full h-full object-cover rounded-full" :alt="$localized(doctor, 'name')" />
                            <span v-else class="text-2xl font-bold text-[#1B365D]">{{ ($localized(doctor, 'name') || 'D').charAt(0) }}</span>
                        </div>
                    </div>
                    <h2 class="mt-3 text-white font-bold">{{ $localized(doctor, 'name') }}</h2>
                    <p class="text-white/70 text-xs mt-0.5">{{ $localized(doctor, 'specialization') }}</p>
                    <span v-if="doctorTypeLabel(doctor.doctor_type)" class="inline-block mt-2 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#C4A265]/20 text-[#C4A265] border border-[#C4A265]/30 uppercase tracking-wider">
                        {{ doctorTypeLabel(doctor.doctor_type) }}
                    </span>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gradient-to-r from-[#1B365D]/5 to-[#C4A265]/5 border border-[#C4A265]/10">
                        <div>
                            <p class="text-[10px] uppercase text-gray-400 tracking-widest font-semibold">{{ isRtl ? 'الرسوم' : 'Fee' }}</p>
                            <p class="text-xl font-bold text-[#1B365D]">{{ fee }} <span class="text-xs font-normal text-gray-400">{{ isRtl ? 'ج.م' : 'EGP' }}</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] uppercase text-gray-400 tracking-widest font-semibold">{{ isRtl ? 'المدة' : 'Duration' }}</p>
                            <p class="text-sm font-semibold text-gray-700">{{ doctor.online_session_duration_minutes || 30 }} {{ isRtl ? 'دقيقة' : 'min' }}</p>
                        </div>
                    </div>
                    <p v-if="$localized(doctor, 'online_consultation_bio')" class="text-xs text-gray-500 leading-relaxed">
                        {{ $localized(doctor, 'online_consultation_bio') }}
                    </p>
                    <Link :href="lp('/online-consultations/doctors')" class="inline-flex items-center gap-1 text-xs text-[#1B365D] font-semibold hover:underline">
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        {{ isRtl ? 'اختر طبيباً آخر' : 'Choose another doctor' }}
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div>
            <!-- Flash error banner (from backend redirect-back) -->
            <div v-if="flashError" class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1 text-sm text-red-700">{{ flashError }}</div>
            </div>

            <!-- Progress -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5">
                <div class="flex items-center justify-between">
                    <div
                        v-for="(step, idx) in [
                            { id: 1, label_ar: 'التاريخ', label_en: 'Date' },
                            { id: 2, label_ar: 'الوقت', label_en: 'Time' },
                            { id: 3, label_ar: 'الدفع', label_en: 'Pay' },
                        ]"
                        :key="step.id"
                        class="flex-1 flex items-center"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition"
                                :class="currentStep >= step.id
                                    ? 'bg-[#C4A265] border-[#C4A265] text-white'
                                    : 'bg-white border-gray-300 text-gray-400'"
                            >
                                <svg v-if="currentStep > step.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span v-else>{{ step.id }}</span>
                            </div>
                            <span class="text-xs font-semibold" :class="currentStep >= step.id ? 'text-[#1B365D]' : 'text-gray-400'">
                                {{ isRtl ? step.label_ar : step.label_en }}
                            </span>
                        </div>
                        <div v-if="idx < 2" class="flex-1 h-0.5 mx-3" :class="currentStep > step.id ? 'bg-[#C4A265]' : 'bg-gray-200'"></div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Date -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                <h3 class="font-bold text-gray-800 mb-1">{{ isRtl ? '١. اختر اليوم' : '1. Pick a date' }}</h3>
                <p class="text-xs text-gray-500 mb-4">{{ isRtl ? 'الأيام المتاحة خلال الأسبوعين القادمين.' : 'Available days in the next 2 weeks.' }}</p>

                <div class="flex gap-2 overflow-x-auto pb-2 day-strip">
                    <button
                        v-for="day in availability"
                        :key="day.date"
                        type="button"
                        @click="pickDay(day)"
                        :disabled="!day.has_available"
                        class="flex-shrink-0 w-[88px] p-3 rounded-xl border-2 text-center transition"
                        :class="[
                            selectedDate === day.date
                                ? 'border-[#C4A265] bg-gradient-to-br from-[#C4A265]/10 to-[#1B365D]/5 shadow-md'
                                : day.has_available
                                    ? 'border-gray-200 hover:border-[#C4A265]/50 bg-white'
                                    : 'border-gray-100 bg-gray-50 opacity-50 cursor-not-allowed'
                        ]"
                    >
                        <p class="text-[10px] uppercase tracking-wider font-semibold"
                           :class="selectedDate === day.date ? 'text-[#C4A265]' : 'text-gray-400'">
                            {{ isRtl ? day.day_name_ar : day.day_name_en.substring(0, 3) }}
                        </p>
                        <p class="text-lg font-bold mt-1"
                           :class="selectedDate === day.date ? 'text-[#1B365D]' : 'text-gray-700'">
                            {{ isRtl ? day.date_label_ar : day.date_label_en }}
                        </p>
                        <p v-if="day.is_today" class="text-[10px] font-semibold mt-0.5" :class="selectedDate === day.date ? 'text-[#C4A265]' : 'text-emerald-500'">
                            {{ isRtl ? 'اليوم' : 'Today' }}
                        </p>
                        <p v-else-if="day.has_available" class="text-[10px] font-semibold mt-0.5 text-emerald-500">
                            {{ isRtl ? 'متاح' : 'Available' }}
                        </p>
                        <p v-else class="text-[10px] font-semibold mt-0.5 text-gray-400">
                            {{ isRtl ? 'ممتلئ' : 'Full' }}
                        </p>
                    </button>
                </div>
            </div>

            <!-- Step 2: Time -->
            <div v-if="selectedDay" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                <h3 class="font-bold text-gray-800 mb-1">{{ isRtl ? '٢. اختر الوقت' : '2. Pick a time' }}</h3>
                <p class="text-xs text-gray-500 mb-4">
                    {{ isRtl ? selectedDay.date_label_ar : selectedDay.date_label_en }} · {{ selectedDay.slots.length }} {{ isRtl ? 'موعد' : 'slots' }}
                </p>

                <div v-if="selectedDay.slots.length === 0" class="text-center py-8 text-sm text-gray-400">
                    {{ isRtl ? 'لا توجد مواعيد في هذا اليوم.' : 'No slots on this day.' }}
                </div>

                <div v-else class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    <button
                        v-for="slot in selectedDay.slots"
                        :key="slot.start_time"
                        type="button"
                        @click="pickSlot(slot)"
                        :disabled="!slot.available"
                        class="px-3 py-2.5 rounded-lg text-sm font-semibold border-2 transition flex items-center justify-center gap-1"
                        :class="[
                            selectedSlot?.start_time === slot.start_time && slot.available
                                ? 'border-[#C4A265] bg-gradient-to-br from-[#C4A265] to-[#D9B985] text-[#1B365D] shadow-md ring-2 ring-[#C4A265]/30'
                                : slot.available
                                    ? 'border-gray-200 bg-white text-gray-700 hover:border-emerald-300 hover:bg-emerald-50/50'
                                    : 'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed line-through'
                        ]"
                    >
                        <svg v-if="slot.available && selectedSlot?.start_time !== slot.start_time" class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ slot.start_time }}
                    </button>
                </div>
            </div>

            <!-- Step 3: Complaint + Confirm -->
            <div v-if="selectedSlot" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                <h3 class="font-bold text-gray-800 mb-1">{{ isRtl ? '٣. التأكيد والدفع' : '3. Confirm & pay' }}</h3>
                <p class="text-xs text-gray-500 mb-4">{{ isRtl ? 'أخبرنا عن مشكلتك لنساعد الطبيب في الاستعداد.' : 'Tell us about your concern so the doctor can prepare.' }}</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            {{ isRtl ? 'الشكوى الرئيسية (اختياري)' : 'Chief complaint (optional)' }}
                        </label>
                        <textarea
                            v-model="form.chief_complaint"
                            rows="4"
                            :placeholder="isRtl ? 'وصف مختصر للأعراض أو سبب الاستشارة...' : 'Short description of symptoms or reason for consultation...'"
                            class="doctorato-input w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"
                        ></textarea>
                        <p v-if="form.errors.chief_complaint" class="mt-1 text-xs text-red-600">{{ form.errors.chief_complaint }}</p>
                    </div>

                    <!-- Summary Card -->
                    <div class="rounded-2xl bg-gradient-to-br from-[#1B365D]/5 to-[#C4A265]/10 border border-[#C4A265]/20 p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center overflow-hidden border border-[#C4A265]/30">
                                <img v-if="doctor.photo" :src="`/storage/${doctor.photo}`" class="w-full h-full object-cover" :alt="$localized(doctor, 'name')" />
                                <span v-else class="font-bold text-[#1B365D]">{{ ($localized(doctor, 'name') || 'D').charAt(0) }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-800 text-sm">{{ $localized(doctor, 'name') }}</p>
                                <p class="text-xs text-gray-500">{{ $localized(doctor, 'specialization') }}</p>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-[#C4A265]/20 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-[10px] uppercase text-gray-400 tracking-widest font-semibold">{{ isRtl ? 'التاريخ' : 'Date' }}</p>
                                <p class="font-semibold text-[#1B365D]">{{ isRtl ? selectedDay?.date_label_ar : selectedDay?.date_label_en }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase text-gray-400 tracking-widest font-semibold">{{ isRtl ? 'الوقت' : 'Time' }}</p>
                                <p class="font-semibold text-[#1B365D]">{{ selectedSlot.start_time }} - {{ selectedSlot.end_time }}</p>
                            </div>
                            <div class="col-span-2 pt-3 border-t border-[#C4A265]/20 flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-600">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                                <span class="text-2xl font-bold text-[#1B365D]">{{ fee }} <span class="text-xs font-normal text-gray-400">{{ isRtl ? 'ج.م' : 'EGP' }}</span></span>
                            </div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 py-3.5 rounded-xl text-sm font-bold text-[#1B365D] bg-gradient-to-r from-[#C4A265] to-[#D9B985] hover:shadow-xl transition-all disabled:opacity-50"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        {{ form.processing ? (isRtl ? 'جارٍ التحويل...' : 'Redirecting...') : (isRtl ? `ادفع الآن ${fee} ج` : `Pay now ${fee} EGP`) }}
                    </button>
                    <p class="text-center text-xs text-gray-400">
                        <svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        {{ isRtl ? 'دفع آمن عبر بوابة مشفّرة' : 'Secure payment via encrypted gateway' }}
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.day-strip::-webkit-scrollbar {
    height: 4px;
}
.day-strip::-webkit-scrollbar-thumb {
    background: rgba(196, 162, 101, 0.3);
    border-radius: 999px;
}
</style>
