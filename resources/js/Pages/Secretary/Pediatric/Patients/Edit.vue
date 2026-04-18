<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import SearchableSelect from '@/Components/Secretary/SearchableSelect.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    familyHistory: Array,
});

const form = useForm({
    full_name: props.patient.full_name || '',
    gender: props.patient.gender || '',
    date_of_birth: props.patient.date_of_birth?.split('T')[0] || '',
    phone: props.patient.phone || '',
    blood_type: props.patient.blood_type || '',
    nationality: props.patient.nationality || '',
    address: props.patient.address || '',
    guardian_name: props.patient.guardian_name || '',
    guardian_relation: props.patient.guardian_relation || '',
    guardian_phone: props.patient.guardian_phone || '',
    guardian_phone2: props.patient.guardian_phone2 || '',
    guardian_email: props.patient.guardian_email || '',
    guardian_occupation: props.patient.guardian_occupation || '',
    birth_type: props.patient.birth_type || '',
    birth_place: props.patient.birth_place || '',
    gestational_age_weeks: props.patient.gestational_age_weeks || '',
    birth_weight_kg: props.patient.birth_weight_kg || '',
    birth_length_cm: props.patient.birth_length_cm || '',
    birth_head_circumference_cm: props.patient.birth_head_circumference_cm || '',
    apgar_1min: props.patient.apgar_1min ?? '',
    apgar_5min: props.patient.apgar_5min ?? '',
    birth_complications: props.patient.birth_complications || [],
    nicu_days: props.patient.nicu_days ?? '',
    feeding_type: props.patient.feeding_type || '',
    pregnancy_complications: props.patient.pregnancy_complications || '',
});

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const activeSection = ref(1);

function submitForm() {
    form.post(`/secretary/pediatric/patients/${props.patient.id}/update`, {
        preserveScroll: true,
    });
}

const genderOptions = computed(() => [
    { value: 'male', label: isRtl.value ? 'ذكر' : 'Male' },
    { value: 'female', label: isRtl.value ? 'أنثى' : 'Female' },
]);

const bloodTypeOptions = [
    { value: 'A+', label: 'A+' }, { value: 'A-', label: 'A-' },
    { value: 'B+', label: 'B+' }, { value: 'B-', label: 'B-' },
    { value: 'AB+', label: 'AB+' }, { value: 'AB-', label: 'AB-' },
    { value: 'O+', label: 'O+' }, { value: 'O-', label: 'O-' },
];

const relationOptions = computed(() => [
    { value: 'father', label: isRtl.value ? 'الأب' : 'Father' },
    { value: 'mother', label: isRtl.value ? 'الأم' : 'Mother' },
    { value: 'grandfather', label: isRtl.value ? 'الجد' : 'Grandfather' },
    { value: 'grandmother', label: isRtl.value ? 'الجدة' : 'Grandmother' },
    { value: 'sibling', label: isRtl.value ? 'أخ/أخت' : 'Sibling' },
    { value: 'other', label: isRtl.value ? 'أخرى' : 'Other' },
]);

const birthTypeOptions = computed(() => [
    { value: 'normal', label: isRtl.value ? 'طبيعية' : 'Normal' },
    { value: 'cesarean', label: isRtl.value ? 'قيصرية' : 'Cesarean' },
    { value: 'vacuum', label: isRtl.value ? 'شفط' : 'Vacuum' },
    { value: 'forceps', label: isRtl.value ? 'ملقط' : 'Forceps' },
]);

const feedingOptions = computed(() => [
    { value: 'breastfed', label: isRtl.value ? 'رضاعة طبيعية' : 'Breastfed' },
    { value: 'formula', label: isRtl.value ? 'رضاعة صناعية' : 'Formula' },
    { value: 'mixed', label: isRtl.value ? 'مختلطة' : 'Mixed' },
]);

const complicationsList = [
    { key: 'jaundice', labelAr: 'يرقان', labelEn: 'Jaundice' },
    { key: 'hypoxia', labelAr: 'نقص أكسجين', labelEn: 'Hypoxia' },
    { key: 'nicu', labelAr: 'حضانة', labelEn: 'NICU' },
    { key: 'infection', labelAr: 'عدوى', labelEn: 'Infection' },
    { key: 'respiratory', labelAr: 'مشاكل تنفسية', labelEn: 'Respiratory issues' },
];

function toggleComplication(key) {
    const arr = [...form.birth_complications];
    const idx = arr.indexOf(key);
    if (idx >= 0) arr.splice(idx, 1);
    else arr.push(key);
    form.birth_complications = arr;
}

const sections = computed(() => [
    { id: 1, title: isRtl.value ? 'معلومات الطفل' : 'Child Information', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 2, title: isRtl.value ? 'معلومات ولي الأمر' : 'Guardian Information', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { id: 3, title: isRtl.value ? 'تاريخ الولادة' : 'Birth History', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
]);
</script>

<template>
    <div class="min-h-screen bg-gray-50/50">
        <!-- Hero -->
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-500 px-4 sm:px-6 pt-6 pb-10">
            <div class="max-w-4xl mx-auto">
                <Link href="/secretary/pediatric/patients" class="inline-flex items-center gap-2 text-white/80 hover:text-white text-sm mb-4 transition-colors">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    {{ isRtl ? 'العودة للقائمة' : 'Back to List' }}
                </Link>
                <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'تعديل بيانات المريض' : 'Edit Patient' }}</h1>
                <p class="text-white/70 mt-1">{{ patient.full_name }} - {{ patient.file_number }}</p>
            </div>
        </div>

        <!-- Form -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 -mt-6">
            <form @submit.prevent="submitForm">
                <!-- Section tabs -->
                <div class="flex gap-2 mb-4 overflow-x-auto scrollbar-none pb-2">
                    <button
                        v-for="s in sections" :key="s.id"
                        type="button"
                        @click="activeSection = s.id"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium whitespace-nowrap transition-all duration-300"
                        :class="activeSection === s.id
                            ? 'bg-white text-emerald-700 shadow-lg shadow-emerald-500/10'
                            : 'bg-white/60 text-gray-500 hover:bg-white hover:text-gray-700'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="s.icon" /></svg>
                        {{ s.title }}
                    </button>
                </div>

                <!-- Section 1: Child Info -->
                <div v-show="activeSection === 1" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 mb-4 transition-all duration-300" :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        {{ isRtl ? 'معلومات الطفل' : 'Child Information' }}
                    </h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'الاسم الكامل' : 'Full Name' }} *</label>
                            <input v-model="form.full_name" type="text" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" :class="form.errors.full_name ? 'border-red-300' : ''" />
                            <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-500">{{ form.errors.full_name }}</p>
                        </div>
                        <SearchableSelect v-model="form.gender" :options="genderOptions" :label="isRtl ? 'الجنس *' : 'Gender *'" :placeholder="isRtl ? 'اختر...' : 'Select...'" accentColor="#4CAF50" :error="form.errors.gender" />
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }} *</label>
                            <input v-model="form.date_of_birth" type="date" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" :class="form.errors.date_of_birth ? 'border-red-300' : ''" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'هاتف' : 'Phone' }}</label>
                            <input v-model="form.phone" type="tel" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <SearchableSelect v-model="form.blood_type" :options="bloodTypeOptions" :label="isRtl ? 'فصيلة الدم' : 'Blood Type'" :placeholder="isRtl ? 'اختر...' : 'Select...'" accentColor="#4CAF50" />
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'الجنسية' : 'Nationality' }}</label>
                            <input v-model="form.nationality" type="text" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'العنوان' : 'Address' }}</label>
                            <input v-model="form.address" type="text" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Guardian -->
                <div v-show="activeSection === 2" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 mb-4 transition-all duration-300" :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        {{ isRtl ? 'معلومات ولي الأمر' : 'Guardian Information' }}
                    </h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'اسم ولي الأمر' : 'Guardian Name' }} *</label>
                            <input v-model="form.guardian_name" type="text" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <SearchableSelect v-model="form.guardian_relation" :options="relationOptions" :label="isRtl ? 'صلة القرابة *' : 'Relation *'" :placeholder="isRtl ? 'اختر...' : 'Select...'" accentColor="#4CAF50" />
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'هاتف ولي الأمر' : 'Guardian Phone' }} *</label>
                            <input v-model="form.guardian_phone" type="tel" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'هاتف احتياطي' : 'Secondary Phone' }}</label>
                            <input v-model="form.guardian_phone2" type="tel" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'البريد الإلكتروني' : 'Email' }}</label>
                            <input v-model="form.guardian_email" type="email" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'المهنة' : 'Occupation' }}</label>
                            <input v-model="form.guardian_occupation" type="text" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                    </div>
                </div>

                <!-- Section 3: Birth History -->
                <div v-show="activeSection === 3" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 mb-4 transition-all duration-300" :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                        {{ isRtl ? 'تاريخ الولادة والحمل' : 'Birth & Pregnancy History' }}
                    </h3>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <SearchableSelect v-model="form.birth_type" :options="birthTypeOptions" :label="isRtl ? 'نوع الولادة' : 'Birth Type'" :placeholder="isRtl ? 'اختر...' : 'Select...'" accentColor="#4CAF50" />
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'مكان الولادة' : 'Birth Place' }}</label>
                            <input v-model="form.birth_place" type="text" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'عمر الحمل (أسابيع)' : 'Gestational Age (weeks)' }}</label>
                            <input v-model="form.gestational_age_weeks" type="number" step="0.5" min="20" max="45" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'وزن الولادة (كجم)' : 'Birth Weight (kg)' }}</label>
                            <input v-model="form.birth_weight_kg" type="number" step="0.001" min="0.3" max="7" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'طول الولادة (سم)' : 'Birth Length (cm)' }}</label>
                            <input v-model="form.birth_length_cm" type="number" step="0.1" min="20" max="65" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'محيط الرأس (سم)' : 'Head Circumference (cm)' }}</label>
                            <input v-model="form.birth_head_circumference_cm" type="number" step="0.1" min="20" max="50" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'APGAR الدقيقة 1' : 'APGAR (1 min)' }}</label>
                            <input v-model="form.apgar_1min" type="number" min="0" max="10" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'APGAR الدقيقة 5' : 'APGAR (5 min)' }}</label>
                            <input v-model="form.apgar_5min" type="number" min="0" max="10" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>
                        <SearchableSelect v-model="form.feeding_type" :options="feedingOptions" :label="isRtl ? 'نوع الرضاعة' : 'Feeding Type'" :placeholder="isRtl ? 'اختر...' : 'Select...'" accentColor="#4CAF50" />

                        <!-- Complications -->
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ isRtl ? 'مضاعفات الولادة' : 'Birth Complications' }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="c in complicationsList" :key="c.key"
                                    type="button"
                                    @click="toggleComplication(c.key)"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all duration-200"
                                    :class="form.birth_complications.includes(c.key)
                                        ? 'bg-emerald-50 border-emerald-300 text-emerald-700'
                                        : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-gray-300'"
                                >
                                    {{ isRtl ? c.labelAr : c.labelEn }}
                                </button>
                            </div>
                        </div>

                        <div v-if="form.birth_complications.includes('nicu')">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'مدة الحضانة (أيام)' : 'NICU Days' }}</label>
                            <input v-model="form.nicu_days" type="number" min="0" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5" />
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'مشاكل أثناء الحمل' : 'Pregnancy Complications' }}</label>
                            <textarea v-model="form.pregnancy_complications" rows="3" class="doctorato-input w-full rounded-xl border-gray-200 focus:border-[#1B365D] focus:ring-[#C4A265]/30/20 text-sm py-2.5"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3 mb-8">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 transition-all duration-300 disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        {{ form.processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'حفظ التعديلات' : 'Save Changes') }}
                    </button>
                    <Link :href="`/secretary/pediatric/patients/${patient.id}`" class="px-6 py-3 bg-gray-100 text-gray-600 font-medium rounded-xl hover:bg-gray-200 transition-colors">
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
