<script setup>
import { ref, computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

defineOptions({ layout: PatientLayout });

const props = defineProps({ seizures: { type: Array, default: () => [] }, headaches: { type: Array, default: () => [] } });
const { lp } = usePatientLocale();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }

const tab = ref('seizure');

const sForm = useForm({ occurred_at: '', seizure_type: '', duration_seconds: '', triggers: '', notes: '' });
function submitSeizure() { sForm.post(lp('/neuropsych/diaries/seizure'), { preserveScroll: true, onSuccess: () => sForm.reset() }); }

const hForm = useForm({ date: '', intensity: '', duration_hours: '', ichd3_type: '', aura: false, meds_taken: '', triggers: '' });
function submitHeadache() { hForm.post(lp('/neuropsych/diaries/headache'), { preserveScroll: true, onSuccess: () => hForm.reset() }); }

function fmt(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB') : ''; }
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ t('My diaries', 'مفكّراتي') }}</h1>

        <div class="flex gap-2 mb-5">
            <button @click="tab = 'seizure'" :class="tab === 'seizure' ? 'bg-[#1B365D] text-white' : 'bg-white text-gray-600 border border-gray-200'" class="px-4 py-2 rounded-xl text-sm font-semibold transition">{{ t('Seizures', 'النوبات') }}</button>
            <button @click="tab = 'headache'" :class="tab === 'headache' ? 'bg-[#1B365D] text-white' : 'bg-white text-gray-600 border border-gray-200'" class="px-4 py-2 rounded-xl text-sm font-semibold transition">{{ t('Headaches', 'الصداع') }}</button>
        </div>

        <!-- Seizure -->
        <div v-if="tab === 'seizure'" class="space-y-5">
            <form @submit.prevent="submitSeizure" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                <FormErrors :errors="sForm.errors" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('When', 'متى') }} *</label><input v-model="sForm.occurred_at" type="datetime-local" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Type', 'النوع') }}</label><input v-model="sForm.seizure_type" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Duration (sec)', 'المدة (ثانية)') }}</label><input v-model.number="sForm.duration_seconds" type="number" min="0" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Triggers', 'المحفّزات') }}</label><input v-model="sForm.triggers" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                </div>
                <div class="flex justify-end"><button :disabled="sForm.processing" class="px-5 py-2 rounded-xl bg-[#1B365D] text-white text-sm font-bold disabled:opacity-50">{{ t('Log seizure', 'تسجيل نوبة') }}</button></div>
            </form>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50">
                <div v-for="s in seizures" :key="s.id" class="px-5 py-3 flex justify-between text-sm">
                    <span class="text-gray-700">{{ s.seizure_type || t('Seizure', 'نوبة') }}</span><span class="text-gray-400">{{ fmt(s.occurred_at) }}</span>
                </div>
                <div v-if="!seizures.length" class="px-5 py-8 text-center text-sm text-gray-400">{{ t('No entries yet', 'لا توجد سجلات') }}</div>
            </div>
        </div>

        <!-- Headache -->
        <div v-else class="space-y-5">
            <form @submit.prevent="submitHeadache" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                <FormErrors :errors="hForm.errors" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Date', 'التاريخ') }} *</label><input v-model="hForm.date" type="date" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Intensity (0-10)', 'الشدة (0-10)') }}</label><input v-model.number="hForm.intensity" type="number" min="0" max="10" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Duration (hrs)', 'المدة (ساعة)') }}</label><input v-model.number="hForm.duration_hours" type="number" min="0" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Meds taken', 'الأدوية') }}</label><input v-model="hForm.meds_taken" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm"><input v-model="hForm.aura" type="checkbox" /> {{ t('Aura', 'هالة') }}</label>
                <div class="flex justify-end"><button :disabled="hForm.processing" class="px-5 py-2 rounded-xl bg-[#1B365D] text-white text-sm font-bold disabled:opacity-50">{{ t('Log headache', 'تسجيل صداع') }}</button></div>
            </form>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50">
                <div v-for="h in headaches" :key="h.id" class="px-5 py-3 flex justify-between text-sm">
                    <span class="text-gray-700">{{ h.ichd3_type || t('Headache', 'صداع') }} · {{ h.intensity ?? '—' }}/10</span><span class="text-gray-400">{{ fmt(h.date) }}</span>
                </div>
                <div v-if="!headaches.length" class="px-5 py-8 text-center text-sm text-gray-400">{{ t('No entries yet', 'لا توجد سجلات') }}</div>
            </div>
        </div>
    </div>
</template>
