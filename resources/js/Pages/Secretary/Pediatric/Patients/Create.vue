<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import SearchableSelect from '@/Components/Secretary/SearchableSelect.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const form = useForm({
    // Section 1: Child Information
    full_name: '',
    gender: '',
    date_of_birth: '',
    phone: '',
    blood_type: '',
    nationality: 'Egyptian',
    address: '',
    // Section 2: Guardian Information
    guardian_name: '',
    guardian_relation: '',
    guardian_phone: '',
    guardian_phone2: '',
    guardian_email: '',
    guardian_occupation: '',
    // Section 3: Birth History
    birth_type: '',
    birth_place: '',
    gestational_age_weeks: '',
    birth_weight_kg: '',
    birth_length_cm: '',
    birth_head_circumference_cm: '',
    apgar_1min: '',
    apgar_5min: '',
    birth_complications: [],
    nicu_days: '',
    feeding_type: '',
    pregnancy_complications: '',
    // Section 4: Family History
    family_history: [],
});

const activeSection = ref(1);
const sections = computed(() => [
    { id: 1, title: isRtl.value ? 'معلومات الطفل' : 'Child Information', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 2, title: isRtl.value ? 'معلومات ولي الأمر' : 'Guardian Information', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { id: 3, title: isRtl.value ? 'تاريخ الولادة' : 'Birth History', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
    { id: 4, title: isRtl.value ? 'التاريخ العائلي' : 'Family History', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
]);

function toggleSection(id) {
    activeSection.value = activeSection.value === id ? null : id;
}

// Options
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

const nationalityOptions = computed(() => [
    { value: 'Egyptian', label: isRtl.value ? 'مصري' : 'Egyptian' },
    { value: 'Saudi', label: isRtl.value ? 'سعودي' : 'Saudi' },
    { value: 'Emirati', label: isRtl.value ? 'إماراتي' : 'Emirati' },
    { value: 'Kuwaiti', label: isRtl.value ? 'كويتي' : 'Kuwaiti' },
    { value: 'Jordanian', label: isRtl.value ? 'أردني' : 'Jordanian' },
    { value: 'Lebanese', label: isRtl.value ? 'لبناني' : 'Lebanese' },
    { value: 'Syrian', label: isRtl.value ? 'سوري' : 'Syrian' },
    { value: 'Iraqi', label: isRtl.value ? 'عراقي' : 'Iraqi' },
    { value: 'Palestinian', label: isRtl.value ? 'فلسطيني' : 'Palestinian' },
    { value: 'Sudanese', label: isRtl.value ? 'سوداني' : 'Sudanese' },
    { value: 'Libyan', label: isRtl.value ? 'ليبي' : 'Libyan' },
    { value: 'Yemeni', label: isRtl.value ? 'يمني' : 'Yemeni' },
    { value: 'Other', label: isRtl.value ? 'أخرى' : 'Other' },
]);

const guardianRelationOptions = computed(() => [
    { value: 'father', label: isRtl.value ? 'أب' : 'Father' },
    { value: 'mother', label: isRtl.value ? 'أم' : 'Mother' },
    { value: 'grandfather', label: isRtl.value ? 'جد' : 'Grandfather' },
    { value: 'grandmother', label: isRtl.value ? 'جدة' : 'Grandmother' },
    { value: 'sibling', label: isRtl.value ? 'أخ/أخت' : 'Sibling' },
    { value: 'other', label: isRtl.value ? 'أخرى' : 'Other' },
]);

const birthTypeOptions = computed(() => [
    { value: 'normal', label: isRtl.value ? 'طبيعية' : 'Normal' },
    { value: 'cesarean', label: isRtl.value ? 'قيصرية' : 'Cesarean' },
    { value: 'vacuum', label: isRtl.value ? 'شفط' : 'Vacuum' },
    { value: 'forceps', label: isRtl.value ? 'ملقط' : 'Forceps' },
]);

const feedingTypeOptions = computed(() => [
    { value: 'breastfeeding', label: isRtl.value ? 'رضاعة طبيعية' : 'Breastfeeding' },
    { value: 'formula', label: isRtl.value ? 'رضاعة صناعية' : 'Formula' },
    { value: 'mixed', label: isRtl.value ? 'مختلطة' : 'Mixed' },
]);

const birthComplicationOptions = computed(() => [
    { value: 'jaundice', label: isRtl.value ? 'يرقان' : 'Jaundice' },
    { value: 'hypoxia', label: isRtl.value ? 'نقص أكسجين' : 'Hypoxia' },
    { value: 'nicu', label: isRtl.value ? 'حضانة' : 'NICU Admission' },
    { value: 'none', label: isRtl.value ? 'لا يوجد' : 'None' },
]);

const familyConditionOptions = computed(() => [
    { value: 'diabetes', label: isRtl.value ? 'سكري' : 'Diabetes' },
    { value: 'hypertension', label: isRtl.value ? 'ضغط دم مرتفع' : 'Hypertension' },
    { value: 'asthma', label: isRtl.value ? 'ربو' : 'Asthma' },
    { value: 'heart_disease', label: isRtl.value ? 'أمراض قلب' : 'Heart Disease' },
    { value: 'epilepsy', label: isRtl.value ? 'صرع' : 'Epilepsy' },
    { value: 'genetic_disorder', label: isRtl.value ? 'اضطراب وراثي' : 'Genetic Disorder' },
    { value: 'cancer', label: isRtl.value ? 'سرطان' : 'Cancer' },
    { value: 'thyroid', label: isRtl.value ? 'غدة درقية' : 'Thyroid Disease' },
    { value: 'anemia', label: isRtl.value ? 'فقر دم' : 'Anemia' },
    { value: 'allergy', label: isRtl.value ? 'حساسية' : 'Allergy' },
    { value: 'other', label: isRtl.value ? 'أخرى' : 'Other' },
]);

const familyMemberOptions = computed(() => [
    { value: 'father', label: isRtl.value ? 'الأب' : 'Father' },
    { value: 'mother', label: isRtl.value ? 'الأم' : 'Mother' },
    { value: 'sibling', label: isRtl.value ? 'أخ/أخت' : 'Sibling' },
    { value: 'grandparent', label: isRtl.value ? 'جد/جدة' : 'Grandparent' },
]);

function toggleComplication(val) {
    if (val === 'none') {
        form.birth_complications = ['none'];
        return;
    }
    const idx = form.birth_complications.indexOf(val);
    form.birth_complications = form.birth_complications.filter(c => c !== 'none');
    if (idx > -1) {
        form.birth_complications.splice(idx, 1);
    } else {
        form.birth_complications.push(val);
    }
}

function addFamilyRow() {
    form.family_history.push({ condition: '', affected_members: [], details: '' });
}

function removeFamilyRow(index) {
    form.family_history.splice(index, 1);
}

function toggleFamilyMember(rowIndex, member) {
    const row = form.family_history[rowIndex];
    const idx = row.affected_members.indexOf(member);
    if (idx > -1) {
        row.affected_members.splice(idx, 1);
    } else {
        row.affected_members.push(member);
    }
}

function submitForm() {
    form.post('/secretary/pediatric/patients', {
        preserveScroll: true,
    });
}

const headerLoaded = ref(false);
const formLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { formLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- HERO HEADER -->
        <div
            class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] overflow-hidden transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
        >
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 30% 50%, #4CAF50 0%, transparent 40%), radial-gradient(circle at 70% 50%, #0d9488 0%, transparent 50%)"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-3">
                    <Link href="/secretary/pediatric/patients" class="inline-flex items-center gap-1.5 text-gray-400 hover:text-white transition-colors text-sm">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        {{ isRtl ? 'العودة' : 'Back' }}
                    </Link>
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                    <span class="w-2 h-2 rounded-full bg-[#4CAF50] animate-pulse"></span>
                    <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'عيادة الأطفال' : 'Pediatric Clinic' }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'تسجيل مريض أطفال جديد' : 'Register New Pediatric Patient' }}</h1>
                <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'أكمل المعلومات المطلوبة لتسجيل المريض' : 'Complete the required information to register the patient' }}</p>
            </div>
        </div>

        <!-- FORM -->
        <form @submit.prevent="submitForm" class="transition-all duration-500" :class="formLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <!-- Section Navigation -->
            <div class="flex flex-wrap gap-2 mb-6">
                <button
                    v-for="section in sections"
                    :key="section.id"
                    type="button"
                    @click="toggleSection(section.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200"
                    :class="activeSection === section.id
                        ? 'bg-[#4CAF50] text-white shadow-lg shadow-[#4CAF50]/20'
                        : 'bg-white text-gray-600 border border-gray-200 hover:border-[#4CAF50]/30 hover:text-[#4CAF50]'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="section.icon" /></svg>
                    {{ section.title }}
                </button>
            </div>

            <!-- ===== SECTION 1: Child Information ===== -->
            <div v-show="activeSection === 1" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden animate-fadeIn">
                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-[#4CAF50]/5 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#4CAF50]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#4CAF50]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ isRtl ? 'معلومات الطفل' : 'Child Information' }}</h3>
                            <p class="text-xs text-gray-500">{{ isRtl ? 'البيانات الشخصية للطفل' : 'Personal details of the child' }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Full Name -->
                        <div class="sm:col-span-2 lg:col-span-1">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ isRtl ? 'الاسم الكامل' : 'Full Name' }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.full_name"
                                type="text"
                                :placeholder="isRtl ? 'أدخل الاسم الكامل للطفل' : 'Enter child full name'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                                :class="form.errors.full_name ? 'border-red-300' : ''"
                            />
                            <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-500">{{ form.errors.full_name }}</p>
                        </div>

                        <!-- Gender -->
                        <div>
                            <SearchableSelect
                                v-model="form.gender"
                                :options="genderOptions"
                                :label="isRtl ? 'الجنس' : 'Gender'"
                                :placeholder="isRtl ? 'اختر الجنس' : 'Select gender'"
                                :error="form.errors.gender"
                                accentColor="#4CAF50"
                            />
                        </div>

                        <!-- Date of Birth -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.date_of_birth"
                                type="date"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                                :class="form.errors.date_of_birth ? 'border-red-300' : ''"
                            />
                            <p v-if="form.errors.date_of_birth" class="mt-1 text-xs text-red-500">{{ form.errors.date_of_birth }}</p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'الهاتف' : 'Phone' }}</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                :placeholder="isRtl ? 'رقم الهاتف' : 'Phone number'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition dir-ltr"
                            />
                        </div>

                        <!-- Blood Type -->
                        <div>
                            <SearchableSelect
                                v-model="form.blood_type"
                                :options="bloodTypeOptions"
                                :label="isRtl ? 'فصيلة الدم' : 'Blood Type'"
                                :placeholder="isRtl ? 'اختر فصيلة الدم' : 'Select blood type'"
                                :error="form.errors.blood_type"
                                accentColor="#4CAF50"
                            />
                        </div>

                        <!-- Nationality -->
                        <div>
                            <SearchableSelect
                                v-model="form.nationality"
                                :options="nationalityOptions"
                                :label="isRtl ? 'الجنسية' : 'Nationality'"
                                :placeholder="isRtl ? 'اختر الجنسية' : 'Select nationality'"
                                accentColor="#4CAF50"
                            />
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'العنوان' : 'Address' }}</label>
                        <textarea
                            v-model="form.address"
                            rows="2"
                            :placeholder="isRtl ? 'عنوان السكن' : 'Residential address'"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition resize-none"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- ===== SECTION 2: Guardian Information ===== -->
            <div v-show="activeSection === 2" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden animate-fadeIn">
                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-[#0d9488]/5 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#0d9488]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ isRtl ? 'معلومات ولي الأمر' : 'Guardian Information' }}</h3>
                            <p class="text-xs text-gray-500">{{ isRtl ? 'بيانات ولي أمر الطفل' : 'Details of the child guardian' }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Guardian Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ isRtl ? 'اسم ولي الأمر' : 'Guardian Name' }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.guardian_name"
                                type="text"
                                :placeholder="isRtl ? 'الاسم الكامل لولي الأمر' : 'Guardian full name'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#0d9488]/20 focus:border-[#0d9488] transition"
                                :class="form.errors.guardian_name ? 'border-red-300' : ''"
                            />
                            <p v-if="form.errors.guardian_name" class="mt-1 text-xs text-red-500">{{ form.errors.guardian_name }}</p>
                        </div>

                        <!-- Guardian Relation -->
                        <div>
                            <SearchableSelect
                                v-model="form.guardian_relation"
                                :options="guardianRelationOptions"
                                :label="isRtl ? 'صلة القرابة' : 'Relation'"
                                :placeholder="isRtl ? 'اختر صلة القرابة' : 'Select relation'"
                                :error="form.errors.guardian_relation"
                                accentColor="#0d9488"
                            />
                        </div>

                        <!-- Guardian Phone -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ isRtl ? 'هاتف ولي الأمر' : 'Guardian Phone' }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.guardian_phone"
                                type="tel"
                                :placeholder="isRtl ? 'رقم الهاتف الرئيسي' : 'Primary phone number'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#0d9488]/20 focus:border-[#0d9488] transition dir-ltr"
                                :class="form.errors.guardian_phone ? 'border-red-300' : ''"
                            />
                            <p v-if="form.errors.guardian_phone" class="mt-1 text-xs text-red-500">{{ form.errors.guardian_phone }}</p>
                        </div>

                        <!-- Guardian Phone 2 -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'هاتف بديل' : 'Alternative Phone' }}</label>
                            <input
                                v-model="form.guardian_phone2"
                                type="tel"
                                :placeholder="isRtl ? 'رقم هاتف بديل' : 'Alternative phone'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#0d9488]/20 focus:border-[#0d9488] transition dir-ltr"
                            />
                        </div>

                        <!-- Guardian Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'البريد الإلكتروني' : 'Email' }}</label>
                            <input
                                v-model="form.guardian_email"
                                type="email"
                                :placeholder="isRtl ? 'البريد الإلكتروني' : 'Email address'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#0d9488]/20 focus:border-[#0d9488] transition dir-ltr"
                            />
                        </div>

                        <!-- Guardian Occupation -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'المهنة' : 'Occupation' }}</label>
                            <input
                                v-model="form.guardian_occupation"
                                type="text"
                                :placeholder="isRtl ? 'مهنة ولي الأمر' : 'Guardian occupation'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#0d9488]/20 focus:border-[#0d9488] transition"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SECTION 3: Birth History ===== -->
            <div v-show="activeSection === 3" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden animate-fadeIn">
                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50/50 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ isRtl ? 'تاريخ الولادة' : 'Birth History' }}</h3>
                            <p class="text-xs text-gray-500">{{ isRtl ? 'معلومات عن ولادة الطفل' : 'Information about the birth of the child' }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Birth Type -->
                        <div>
                            <SearchableSelect
                                v-model="form.birth_type"
                                :options="birthTypeOptions"
                                :label="isRtl ? 'نوع الولادة' : 'Birth Type'"
                                :placeholder="isRtl ? 'اختر نوع الولادة' : 'Select birth type'"
                                accentColor="#4CAF50"
                            />
                        </div>

                        <!-- Birth Place -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'مكان الولادة' : 'Birth Place' }}</label>
                            <input
                                v-model="form.birth_place"
                                type="text"
                                :placeholder="isRtl ? 'المستشفى أو المكان' : 'Hospital or location'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                            />
                        </div>

                        <!-- Gestational Age -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'عمر الحمل (أسابيع)' : 'Gestational Age (weeks)' }}</label>
                            <input
                                v-model="form.gestational_age_weeks"
                                type="number"
                                min="20"
                                max="45"
                                :placeholder="isRtl ? 'أسابيع' : 'Weeks'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                            />
                        </div>

                        <!-- Birth Weight -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'الوزن عند الولادة (كغ)' : 'Birth Weight (kg)' }}</label>
                            <input
                                v-model="form.birth_weight_kg"
                                type="number"
                                step="0.01"
                                min="0.3"
                                max="7"
                                :placeholder="isRtl ? 'كيلوغرام' : 'Kilograms'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                            />
                        </div>

                        <!-- Birth Length -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'الطول عند الولادة (سم)' : 'Birth Length (cm)' }}</label>
                            <input
                                v-model="form.birth_length_cm"
                                type="number"
                                step="0.1"
                                min="20"
                                max="65"
                                :placeholder="isRtl ? 'سنتيمتر' : 'Centimeters'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                            />
                        </div>

                        <!-- Head Circumference -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'محيط الرأس (سم)' : 'Head Circumference (cm)' }}</label>
                            <input
                                v-model="form.birth_head_circumference_cm"
                                type="number"
                                step="0.1"
                                min="20"
                                max="50"
                                :placeholder="isRtl ? 'سنتيمتر' : 'Centimeters'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                            />
                        </div>

                        <!-- Apgar 1min -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'أبغار (1 دقيقة)' : 'Apgar (1 min)' }}</label>
                            <input
                                v-model="form.apgar_1min"
                                type="number"
                                min="0"
                                max="10"
                                :placeholder="isRtl ? '0-10' : '0-10'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                            />
                        </div>

                        <!-- Apgar 5min -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'أبغار (5 دقائق)' : 'Apgar (5 min)' }}</label>
                            <input
                                v-model="form.apgar_5min"
                                type="number"
                                min="0"
                                max="10"
                                :placeholder="isRtl ? '0-10' : '0-10'"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                            />
                        </div>

                        <!-- Feeding Type -->
                        <div>
                            <SearchableSelect
                                v-model="form.feeding_type"
                                :options="feedingTypeOptions"
                                :label="isRtl ? 'نوع التغذية' : 'Feeding Type'"
                                :placeholder="isRtl ? 'اختر نوع التغذية' : 'Select feeding type'"
                                accentColor="#4CAF50"
                            />
                        </div>
                    </div>

                    <!-- Birth Complications -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ isRtl ? 'مضاعفات الولادة' : 'Birth Complications' }}</label>
                        <div class="flex flex-wrap gap-3">
                            <button
                                v-for="option in birthComplicationOptions"
                                :key="option.value"
                                type="button"
                                @click="toggleComplication(option.value)"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all duration-200"
                                :class="form.birth_complications.includes(option.value)
                                    ? 'bg-[#4CAF50]/10 border-[#4CAF50]/30 text-[#4CAF50]'
                                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'"
                            >
                                <svg v-if="form.birth_complications.includes(option.value)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ option.label }}
                            </button>
                        </div>
                    </div>

                    <!-- NICU Days (show if nicu selected) -->
                    <div v-if="form.birth_complications.includes('nicu')" class="max-w-xs">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'عدد أيام الحضانة' : 'NICU Days' }}</label>
                        <input
                            v-model="form.nicu_days"
                            type="number"
                            min="0"
                            :placeholder="isRtl ? 'عدد الأيام' : 'Number of days'"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition"
                        />
                    </div>

                    <!-- Pregnancy Complications -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'مضاعفات الحمل' : 'Pregnancy Complications' }}</label>
                        <textarea
                            v-model="form.pregnancy_complications"
                            rows="2"
                            :placeholder="isRtl ? 'أي مضاعفات أثناء الحمل' : 'Any complications during pregnancy'"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition resize-none"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- ===== SECTION 4: Family History ===== -->
            <div v-show="activeSection === 4" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden animate-fadeIn">
                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50/50 to-transparent">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ isRtl ? 'التاريخ العائلي' : 'Family History' }}</h3>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'الأمراض الوراثية والعائلية' : 'Hereditary and family conditions' }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="addFamilyRow"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-[#4CAF50] bg-[#4CAF50]/5 hover:bg-[#4CAF50]/10 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ isRtl ? 'إضافة حالة' : 'Add Condition' }}
                        </button>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <div v-if="form.family_history.length === 0" class="py-12 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <p class="text-sm text-gray-500 font-medium">{{ isRtl ? 'لا توجد حالات عائلية مسجلة' : 'No family conditions recorded' }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'اضغط "إضافة حالة" لتسجيل التاريخ المرضي العائلي' : 'Click "Add Condition" to record family medical history' }}</p>
                    </div>

                    <div
                        v-for="(row, rowIndex) in form.family_history"
                        :key="rowIndex"
                        class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500">{{ isRtl ? `الحالة ${rowIndex + 1}` : `Condition ${rowIndex + 1}` }}</span>
                            <button
                                type="button"
                                @click="removeFamilyRow(rowIndex)"
                                class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Condition -->
                            <div>
                                <SearchableSelect
                                    v-model="row.condition"
                                    :options="familyConditionOptions"
                                    :label="isRtl ? 'الحالة المرضية' : 'Condition'"
                                    :placeholder="isRtl ? 'اختر الحالة' : 'Select condition'"
                                    accentColor="#4CAF50"
                                />
                            </div>

                            <!-- Details -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'تفاصيل' : 'Details' }}</label>
                                <input
                                    v-model="row.details"
                                    type="text"
                                    :placeholder="isRtl ? 'ملاحظات إضافية' : 'Additional notes'"
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#4CAF50]/20 focus:border-[#4CAF50] transition bg-white"
                                />
                            </div>
                        </div>

                        <!-- Affected Members -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-2">{{ isRtl ? 'الأفراد المتأثرون' : 'Affected Members' }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="member in familyMemberOptions"
                                    :key="member.value"
                                    type="button"
                                    @click="toggleFamilyMember(rowIndex, member.value)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-all"
                                    :class="row.affected_members.includes(member.value)
                                        ? 'bg-[#4CAF50]/10 border-[#4CAF50]/30 text-[#4CAF50]'
                                        : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'"
                                >
                                    <svg v-if="row.affected_members.includes(member.value)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    {{ member.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-100">
                <Link
                    href="/secretary/pediatric/patients"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                >
                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#4CAF50] hover:bg-[#43A047] disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-[#4CAF50]/20"
                >
                    <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    {{ form.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'تسجيل المريض' : 'Register Patient') }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
