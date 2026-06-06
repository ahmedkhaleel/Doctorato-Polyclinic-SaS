<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    consultation: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function lp(path) { return `/${locale.value}/patient${path}`; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || '';
}

function formatDate(d) {
    if (!d) return '';
    const dt = new Date(d);
    return dt.toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}

function formatTime(t) {
    if (!t) return '';
    return String(t).substring(0, 5);
}
</script>

<template>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-br from-emerald-50 via-white to-[#C4A265]/10 p-8 text-center relative overflow-hidden">
                <!-- Animated checkmark -->
                <div class="w-24 h-24 mx-auto relative">
                    <div class="absolute inset-0 rounded-full bg-emerald-100 animate-ping opacity-60"></div>
                    <div class="relative w-24 h-24 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <h1 class="mt-6 text-2xl lg:text-3xl font-bold text-[#1B365D]">
                    {{ isRtl ? 'تم حجز استشارتك بنجاح!' : 'Your consultation is booked!' }}
                </h1>
                <p class="mt-2 text-sm text-gray-500">
                    {{ isRtl ? 'سنرسل لك تذكيراً قبل الموعد بوقت كافٍ.' : 'We will remind you before the scheduled time.' }}
                </p>
            </div>

            <div class="p-6 space-y-4">
                <!-- Consultation number -->
                <div class="flex items-center justify-center gap-2">
                    <span class="text-xs text-gray-400">{{ isRtl ? 'رقم الاستشارة' : 'Consultation #' }}</span>
                    <span class="font-mono text-sm font-bold text-[#1B365D]">{{ consultation.consultation_number }}</span>
                </div>

                <!-- Details -->
                <div class="rounded-2xl border border-[#C4A265]/20 bg-gradient-to-br from-[#1B365D]/5 to-[#C4A265]/5 p-5 space-y-3">
                    <div class="flex items-center gap-3 pb-3 border-b border-[#C4A265]/20">
                        <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center overflow-hidden border border-[#C4A265]/30">
                            <img v-if="consultation.doctor?.photo" :src="`/storage/${consultation.doctor.photo}`" class="w-full h-full object-cover" alt="" />
                            <span v-else class="font-bold text-[#1B365D]">{{ ($localized(consultation.doctor, 'name') || 'D').charAt(0) }}</span>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $localized(consultation.doctor, 'name') }}</p>
                            <p class="text-xs text-gray-500">{{ $localized(consultation.doctor, 'specialization') }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] uppercase text-gray-400 tracking-widest font-semibold">{{ isRtl ? 'التاريخ' : 'Date' }}</p>
                            <p class="text-sm font-semibold text-[#1B365D]">{{ formatDate(consultation.scheduled_date) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase text-gray-400 tracking-widest font-semibold">{{ isRtl ? 'الوقت' : 'Time' }}</p>
                            <p class="text-sm font-semibold text-[#1B365D]">{{ formatTime(consultation.start_time) }} - {{ formatTime(consultation.end_time) }}</p>
                        </div>
                        <div class="col-span-2 flex items-center justify-between pt-3 border-t border-[#C4A265]/20">
                            <span class="text-xs font-semibold text-gray-600">{{ isRtl ? 'المدفوع' : 'Paid' }}</span>
                            <span class="text-lg font-bold text-emerald-600">{{ Number(consultation.fee || 0).toFixed(0) }} {{ isRtl ? 'ج.م' : 'EGP' }}</span>
                        </div>
                    </div>
                </div>

                <!-- CTAs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3">
                    <Link :href="lp('/online-consultations')" class="inline-flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#1B365D] to-[#22406F] hover:shadow-lg transition-all">
                        {{ isRtl ? 'عرض استشاراتي' : 'View my consultations' }}
                    </Link>
                    <Link :href="lp('/online-consultations/doctors')" class="inline-flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold text-[#1B365D] border-2 border-[#C4A265]/40 hover:bg-[#C4A265]/10 transition-all">
                        {{ isRtl ? 'احجز استشارة أخرى' : 'Book another' }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
