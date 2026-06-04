<script setup>
import { ref, computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import ScaleRunner from '@/Components/Clinical/ScaleRunner.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    scales: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
});

const { lp } = usePatientLocale();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }

const active = ref(null); // currently-filling scale
const form = useForm({ scale_key: '', answers: {} });

function open(scale) {
    active.value = scale;
    form.scale_key = scale.key;
    form.answers = {};
    form.clearErrors();
}
function submit() {
    form.post(lp('/neuropsych/scales'), {
        preserveScroll: true,
        onSuccess: () => { active.value = null; form.reset(); },
    });
}
function fmt(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB') : ''; }
function scaleName(key) {
    const s = props.scales.find(x => x.key === key);
    return s ? t(s.name_en, s.name_ar) : key;
}
const complete = computed(() => active.value && Object.keys(form.answers).length === active.value.items.length);
</script>

<template>
    <div>
        <div class="flex items-center gap-3 mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ t('My questionnaires', 'استبياناتي') }}</h1>
        </div>

        <!-- Filling a scale -->
        <div v-if="active" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800">{{ t(active.name_en, active.name_ar) }}</h2>
                <button type="button" @click="active = null" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')"
                    class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form @submit.prevent="submit" class="space-y-4">
                <FormErrors :errors="form.errors" />
                <ScaleRunner :scale="active" v-model="form.answers" />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="active = null" class="px-4 py-2 rounded-xl bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                    <button :disabled="form.processing || !complete"
                        class="px-5 py-2 rounded-xl bg-[#1B365D] text-white text-sm font-bold disabled:opacity-50">
                        {{ form.processing ? t('Submitting…', 'جارٍ الإرسال…') : t('Submit', 'إرسال') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Available scales -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <button v-for="s in scales" :key="s.key" type="button" @click="open(s)"
                class="text-start bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-[#C4A265]/40 hover:shadow transition">
                <p class="text-sm font-bold text-gray-800">{{ t(s.name_en, s.name_ar) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ s.items.length }} {{ t('questions', 'سؤال') }}</p>
            </button>
            <div v-if="!scales.length" class="sm:col-span-2 bg-white rounded-2xl border border-dashed border-gray-200 p-10 text-center">
                <p class="text-sm text-gray-500">{{ t('No questionnaires assigned right now', 'لا توجد استبيانات حاليًا') }}</p>
            </div>
        </div>

        <!-- Recent results -->
        <div v-if="recent.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">{{ t('Recent results', 'النتائج الأخيرة') }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="(r, i) in recent" :key="i" class="px-5 py-3 flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700">{{ scaleName(r.scale_key) }}</span>
                    <span class="text-gray-500">{{ r.score }} · {{ fmt(r.taken_at) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
