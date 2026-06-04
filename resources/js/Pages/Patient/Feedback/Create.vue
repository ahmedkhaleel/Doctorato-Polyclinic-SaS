<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    visit: Object,
    improvementAreas: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}

const form = useForm({
    overall_rating: 0,
    doctor_rating: 0,
    staff_rating: 0,
    cleanliness_rating: 0,
    waiting_time_rating: 0,
    communication_rating: 0,
    comments: '',
    would_recommend: null,
    improvement_areas: [],
    nps_score: null,
});

const ratingFields = computed(() => [
    { key: 'overall_rating',       labelAr: 'التقييم العام',          labelEn: 'Overall',          required: true },
    { key: 'doctor_rating',        labelAr: 'الطبيب',                 labelEn: 'Doctor' },
    { key: 'staff_rating',         labelAr: 'الموظفون',               labelEn: 'Staff' },
    { key: 'cleanliness_rating',   labelAr: 'النظافة',                labelEn: 'Cleanliness' },
    { key: 'waiting_time_rating',  labelAr: 'وقت الانتظار',           labelEn: 'Waiting time' },
    { key: 'communication_rating', labelAr: 'التواصل وشرح العلاج',   labelEn: 'Communication' },
]);

function rate(field, value) { form[field] = value; }

const improvementOptions = computed(() =>
    Object.entries(props.improvementAreas || {}).map(([key, labels]) => ({
        value: key,
        label: locale.value === 'ar' ? labels.ar : labels.en,
    }))
);

function toggleImprovement(value) {
    const idx = form.improvement_areas.indexOf(value);
    if (idx >= 0) form.improvement_areas.splice(idx, 1);
    else form.improvement_areas.push(value);
}

function submit() {
    form.post(lp(`/feedback/${props.visit.id}`), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('/feedback')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'تقييم زيارتك' : 'Rate your visit' }}</h1>
        </div>

        <!-- Visit context -->
        <div class="bg-gradient-to-r from-[#FAF7F0] to-white rounded-2xl border border-[#C4A265]/30 p-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center text-white font-bold flex-shrink-0">
                    {{ ($localized(visit.doctor, 'name') || '?').charAt(0).toUpperCase() }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $localized(visit.doctor, 'name') || '—' }}</p>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                        {{ $localized(visit.service, 'name') || '—' }} · {{ visit.visit_date }}
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Star ratings -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'قيّم تجربتك' : 'Rate your experience' }}</h2>
                <div class="space-y-3">
                    <div v-for="r in ratingFields" :key="r.key"
                         class="flex items-center justify-between gap-4 py-2 border-b border-gray-50 last:border-0">
                        <p class="text-sm text-gray-700 flex-1">
                            {{ isRtl ? r.labelAr : r.labelEn }}
                            <span v-if="r.required" class="text-red-500">*</span>
                        </p>
                        <div class="flex items-center gap-1">
                            <button v-for="i in 5" :key="i" type="button" @click="rate(r.key, i)"
                                    class="p-0.5 transition-transform hover:scale-110" :aria-label="isRtl ? 'تقييم' : 'Rate'" :title="isRtl ? 'تقييم' : 'Rate'">
                                <svg class="w-7 h-7"
                                     :class="i <= form[r.key] ? 'text-amber-400' : 'text-gray-200 hover:text-amber-200'"
                                     fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.overall_rating" class="text-xs text-red-600 mt-2">{{ form.errors.overall_rating }}</p>
            </div>

            <!-- Recommend + NPS -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-5">
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        {{ isRtl ? 'هل ستوصي بنا لأصدقائك؟' : 'Would you recommend us to friends?' }}
                    </p>
                    <div class="flex gap-2">
                        <button type="button" @click="form.would_recommend = true"
                                :class="form.would_recommend === true
                                    ? 'bg-emerald-500 text-white border-emerald-500'
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-emerald-50'"
                                class="flex-1 px-4 py-2.5 rounded-lg border text-sm font-semibold transition">
                            <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg> {{ isRtl ? 'نعم' : 'Yes' }}
                        </button>
                        <button type="button" @click="form.would_recommend = false"
                                :class="form.would_recommend === false
                                    ? 'bg-red-500 text-white border-red-500'
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-red-50'"
                                class="flex-1 px-4 py-2.5 rounded-lg border text-sm font-semibold transition">
                            <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/></svg> {{ isRtl ? 'لا' : 'No' }}
                        </button>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        {{ isRtl
                            ? 'على مقياس من 0 إلى 10، كم احتمال أن توصي بنا؟'
                            : 'On a scale of 0–10, how likely are you to recommend us?' }}
                    </p>
                    <div class="grid grid-cols-11 gap-1">
                        <button v-for="n in 11" :key="n - 1" type="button" @click="form.nps_score = n - 1"
                                :class="form.nps_score === (n - 1)
                                    ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                                class="px-1 py-2 rounded-md border text-xs font-bold transition">
                            {{ n - 1 }}
                        </button>
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-400 mt-1.5 px-1">
                        <span>{{ isRtl ? 'لن أوصي' : 'Not at all' }}</span>
                        <span>{{ isRtl ? 'سأوصي بالتأكيد' : 'Extremely likely' }}</span>
                    </div>
                </div>
            </div>

            <!-- Improvement areas -->
            <div v-if="improvementOptions.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-bold text-gray-800 mb-2">
                    {{ isRtl ? 'ما الذي يمكننا تحسينه؟' : 'What could we improve?' }}
                </h2>
                <p class="text-[11px] text-gray-500 mb-3">{{ isRtl ? 'اختر كل ما ينطبق' : 'Select all that apply' }}</p>
                <div class="flex flex-wrap gap-2">
                    <button v-for="opt in improvementOptions" :key="opt.value" type="button"
                            @click="toggleImprovement(opt.value)"
                            :class="form.improvement_areas.includes(opt.value)
                                ? 'bg-[var(--brand-primary)]/10 border-[var(--brand-primary)] text-[var(--brand-primary)]'
                                : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="px-3 py-1.5 rounded-full border text-xs font-medium transition">
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <!-- Comments -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <label class="block text-sm font-bold text-gray-800 mb-2">
                    {{ isRtl ? 'تعليق إضافي (اختياري)' : 'Additional comments (optional)' }}
                </label>
                <textarea v-model="form.comments" rows="4" maxlength="2000"
                          :placeholder="isRtl ? 'شاركنا تجربتك بالتفصيل...' : 'Tell us more about your experience...'"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/30 resize-none"></textarea>
                <p class="text-[10px] text-gray-400 mt-1">{{ form.comments.length }} / 2000</p>
            </div>

            <!-- Submit -->
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing || !form.overall_rating"
                        class="px-6 py-3 rounded-xl bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] text-white text-sm font-bold shadow-md disabled:opacity-50 transition">
                    {{ form.processing ? (isRtl ? 'جارٍ الإرسال...' : 'Submitting...') : (isRtl ? '✓ إرسال التقييم' : '✓ Submit feedback') }}
                </button>
                <Link :href="lp('/feedback')"
                      class="px-4 py-3 rounded-xl bg-white border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">
                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                </Link>
            </div>
        </form>
    </div>
</template>
