<script setup>
import { computed, ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    doctors: Array,
    filters: Object,
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

const moduleFilter = ref(props.filters?.module || '');

const modules = [
    { id: '', label_ar: 'كل التخصصات', label_en: 'All specialties' },
    { id: 'derma', label_ar: 'الجلدية', label_en: 'Dermatology' },
    { id: 'dental', label_ar: 'الأسنان', label_en: 'Dental' },
    { id: 'pediatric', label_ar: 'الأطفال', label_en: 'Pediatric' },
];

function applyFilter(val) {
    moduleFilter.value = val;
    router.get(lp('/online-consultations/doctors'), val ? { module: val } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function doctorTypeLabel(type) {
    if (type === 'consultant') return isRtl.value ? 'استشاري' : 'Consultant';
    if (type === 'specialist') return isRtl.value ? 'أخصائي' : 'Specialist';
    return '';
}
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B365D] via-[#22406F] to-[#1B365D] p-6 lg:p-8 mb-6 shadow-lg">
            <div class="relative flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#D9B985] flex items-center justify-center flex-shrink-0 shadow-md">
                    <svg class="w-7 h-7 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-white">{{ isRtl ? 'اختر طبيبك' : 'Choose your doctor' }}</h1>
                    <p class="text-white/70 text-sm mt-1 max-w-xl">
                        {{ isRtl ? 'كل أطبائنا المعتمدين لإجراء الاستشارات الأونلاين عبر فيديو كول آمن وعالي الجودة.' : 'All our certified doctors available for secure, high-quality online video consultations.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button
                v-for="m in modules"
                :key="m.id"
                @click="applyFilter(m.id)"
                class="px-4 py-2 rounded-xl text-xs font-semibold border transition"
                :class="moduleFilter === m.id
                    ? 'bg-[#1B365D] text-white border-[#1B365D]'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-[#C4A265]/60 hover:text-[#1B365D]'"
            >
                {{ isRtl ? m.label_ar : m.label_en }}
            </button>
        </div>

        <!-- Empty -->
        <div v-if="doctors.length === 0" class="bg-white rounded-2xl p-12 text-center border border-gray-100">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <p class="text-gray-600 font-medium">
                {{ isRtl ? 'لا يوجد أطباء متاحون حالياً للاستشارات الأونلاين.' : 'No doctors available for online consultations right now.' }}
            </p>
        </div>

        <!-- Doctors Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div v-for="doc in doctors" :key="doc.id" class="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:border-[#C4A265]/40 transition-all duration-300">
                <div class="p-5">
                    <!-- Photo -->
                    <div class="flex items-start gap-4">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 rounded-full p-[2px] bg-gradient-to-br from-[#C4A265] to-[#1B365D]">
                                <div class="w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden">
                                    <img v-if="doc.photo" :src="`/storage/${doc.photo}`" class="w-full h-full object-cover rounded-full" :alt="$localized(doc, 'name')" />
                                    <span v-else class="text-2xl font-bold text-[#1B365D]">{{ ($localized(doc, 'name') || 'D').charAt(0) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-800 leading-tight">{{ $localized(doc, 'name') }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $localized(doc, 'specialization') }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span v-if="doctorTypeLabel(doc.doctor_type)" class="inline-flex items-center text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#C4A265]/10 text-[#C4A265] border border-[#C4A265]/20 uppercase tracking-wider">
                                    {{ doctorTypeLabel(doc.doctor_type) }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-500">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    4.8
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bio -->
                    <p v-if="$localized(doc, 'online_consultation_bio')" class="text-xs text-gray-500 mt-4 line-clamp-3 leading-relaxed">
                        {{ $localized(doc, 'online_consultation_bio') }}
                    </p>

                    <!-- Fee & Duration -->
                    <div class="mt-4 flex items-center justify-between p-3 rounded-xl bg-gradient-to-r from-[#1B365D]/5 to-[#C4A265]/5 border border-[#C4A265]/10">
                        <div>
                            <p class="text-[10px] uppercase text-gray-400 tracking-widest font-semibold">{{ isRtl ? 'رسوم الاستشارة' : 'Fee' }}</p>
                            <p class="text-lg font-bold text-[#1B365D]">{{ Number(doc.online_consultation_fee || 0).toFixed(0) }} <span class="text-xs font-normal text-gray-400">{{ isRtl ? 'ج.م' : 'EGP' }}</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] uppercase text-gray-400 tracking-widest font-semibold">{{ isRtl ? 'المدة' : 'Duration' }}</p>
                            <p class="text-sm font-semibold text-gray-700">{{ doc.online_session_duration_minutes || 30 }} {{ isRtl ? 'دقيقة' : 'min' }}</p>
                        </div>
                    </div>

                    <!-- CTA -->
                    <Link :href="lp(`/online-consultations/book/${doc.id}`)"
                        class="mt-4 w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-gradient-to-r from-[#C4A265] to-[#D9B985] hover:shadow-lg hover:from-[#D9B985] hover:to-[#C4A265] transition-all">
                        {{ isRtl ? 'اختر موعد' : 'Choose slot' }}
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
