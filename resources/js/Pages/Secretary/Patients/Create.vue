<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const form = useForm({
    full_name: '',
    phone: '',
    phone2: '',
    email: '',
    date_of_birth: '',
    gender: 'female',
    nationality: 'Egyptian',
    address: '',
    occupation: '',
    referral_source: 'walk_in',
    referred_by: '',
    medical_notes: '',
    photo: null,
});

const nationalityOptions = [
    { value: 'Egyptian', ar: 'مصري', en: 'Egyptian' },
    { value: 'Saudi', ar: 'سعودي', en: 'Saudi' },
    { value: 'Emirati', ar: 'إماراتي', en: 'Emirati' },
    { value: 'Kuwaiti', ar: 'كويتي', en: 'Kuwaiti' },
    { value: 'Qatari', ar: 'قطري', en: 'Qatari' },
    { value: 'Bahraini', ar: 'بحريني', en: 'Bahraini' },
    { value: 'Omani', ar: 'عماني', en: 'Omani' },
    { value: 'Jordanian', ar: 'أردني', en: 'Jordanian' },
    { value: 'Lebanese', ar: 'لبناني', en: 'Lebanese' },
    { value: 'Syrian', ar: 'سوري', en: 'Syrian' },
    { value: 'Iraqi', ar: 'عراقي', en: 'Iraqi' },
    { value: 'Palestinian', ar: 'فلسطيني', en: 'Palestinian' },
    { value: 'Yemeni', ar: 'يمني', en: 'Yemeni' },
    { value: 'Libyan', ar: 'ليبي', en: 'Libyan' },
    { value: 'Tunisian', ar: 'تونسي', en: 'Tunisian' },
    { value: 'Algerian', ar: 'جزائري', en: 'Algerian' },
    { value: 'Moroccan', ar: 'مغربي', en: 'Moroccan' },
    { value: 'Sudanese', ar: 'سوداني', en: 'Sudanese' },
    { value: 'Somali', ar: 'صومالي', en: 'Somali' },
    { value: 'Mauritanian', ar: 'موريتاني', en: 'Mauritanian' },
    { value: 'Djiboutian', ar: 'جيبوتي', en: 'Djiboutian' },
    { value: 'Comoran', ar: 'قمري', en: 'Comoran' },
    { value: 'Turkish', ar: 'تركي', en: 'Turkish' },
    { value: 'Iranian', ar: 'إيراني', en: 'Iranian' },
    { value: 'Pakistani', ar: 'باكستاني', en: 'Pakistani' },
    { value: 'Indian', ar: 'هندي', en: 'Indian' },
    { value: 'Bangladeshi', ar: 'بنغلاديشي', en: 'Bangladeshi' },
    { value: 'Sri Lankan', ar: 'سريلانكي', en: 'Sri Lankan' },
    { value: 'Afghan', ar: 'أفغاني', en: 'Afghan' },
    { value: 'Filipino', ar: 'فلبيني', en: 'Filipino' },
    { value: 'Indonesian', ar: 'إندونيسي', en: 'Indonesian' },
    { value: 'Malaysian', ar: 'ماليزي', en: 'Malaysian' },
    { value: 'Chinese', ar: 'صيني', en: 'Chinese' },
    { value: 'Japanese', ar: 'ياباني', en: 'Japanese' },
    { value: 'Korean', ar: 'كوري', en: 'Korean' },
    { value: 'Thai', ar: 'تايلاندي', en: 'Thai' },
    { value: 'Vietnamese', ar: 'فيتنامي', en: 'Vietnamese' },
    { value: 'Nepali', ar: 'نيبالي', en: 'Nepali' },
    { value: 'Ethiopian', ar: 'إثيوبي', en: 'Ethiopian' },
    { value: 'Eritrean', ar: 'إريتري', en: 'Eritrean' },
    { value: 'Nigerian', ar: 'نيجيري', en: 'Nigerian' },
    { value: 'Kenyan', ar: 'كيني', en: 'Kenyan' },
    { value: 'South African', ar: 'جنوب أفريقي', en: 'South African' },
    { value: 'Ghanaian', ar: 'غاني', en: 'Ghanaian' },
    { value: 'Tanzanian', ar: 'تنزاني', en: 'Tanzanian' },
    { value: 'Ugandan', ar: 'أوغندي', en: 'Ugandan' },
    { value: 'American', ar: 'أمريكي', en: 'American' },
    { value: 'Canadian', ar: 'كندي', en: 'Canadian' },
    { value: 'Mexican', ar: 'مكسيكي', en: 'Mexican' },
    { value: 'Brazilian', ar: 'برازيلي', en: 'Brazilian' },
    { value: 'Argentine', ar: 'أرجنتيني', en: 'Argentine' },
    { value: 'Colombian', ar: 'كولومبي', en: 'Colombian' },
    { value: 'British', ar: 'بريطاني', en: 'British' },
    { value: 'French', ar: 'فرنسي', en: 'French' },
    { value: 'German', ar: 'ألماني', en: 'German' },
    { value: 'Italian', ar: 'إيطالي', en: 'Italian' },
    { value: 'Spanish', ar: 'إسباني', en: 'Spanish' },
    { value: 'Dutch', ar: 'هولندي', en: 'Dutch' },
    { value: 'Belgian', ar: 'بلجيكي', en: 'Belgian' },
    { value: 'Swiss', ar: 'سويسري', en: 'Swiss' },
    { value: 'Austrian', ar: 'نمساوي', en: 'Austrian' },
    { value: 'Swedish', ar: 'سويدي', en: 'Swedish' },
    { value: 'Norwegian', ar: 'نرويجي', en: 'Norwegian' },
    { value: 'Danish', ar: 'دنماركي', en: 'Danish' },
    { value: 'Finnish', ar: 'فنلندي', en: 'Finnish' },
    { value: 'Greek', ar: 'يوناني', en: 'Greek' },
    { value: 'Portuguese', ar: 'برتغالي', en: 'Portuguese' },
    { value: 'Polish', ar: 'بولندي', en: 'Polish' },
    { value: 'Romanian', ar: 'روماني', en: 'Romanian' },
    { value: 'Ukrainian', ar: 'أوكراني', en: 'Ukrainian' },
    { value: 'Russian', ar: 'روسي', en: 'Russian' },
    { value: 'Australian', ar: 'أسترالي', en: 'Australian' },
    { value: 'New Zealander', ar: 'نيوزيلندي', en: 'New Zealander' },
    { value: 'Other', ar: 'أخرى', en: 'Other' },
];

const referralOptions = [
    { value: 'walk_in', label: isRtl.value ? 'حضور مباشر' : 'Walk-in' },
    { value: 'social_media', label: isRtl.value ? 'وسائل التواصل' : 'Social Media' },
    { value: 'google', label: isRtl.value ? 'جوجل' : 'Google' },
    { value: 'friend', label: isRtl.value ? 'صديق' : 'Friend' },
    { value: 'doctor', label: isRtl.value ? 'طبيب' : 'Doctor' },
    { value: 'advertisement', label: isRtl.value ? 'إعلان' : 'Advertisement' },
    { value: 'other', label: isRtl.value ? 'أخرى' : 'Other' },
];

function submit() {
    form.post('/secretary/patients', {
        forceFormData: true,
    });
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-6">
            <Link href="/secretary/patients" class="inline-flex items-center gap-1 text-xs text-teal-600 hover:text-teal-800 font-medium mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                {{ isRtl ? 'العودة للمرضى' : 'Back to Patients' }}
            </Link>
            <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'إضافة مريض جديد' : 'Add New Patient' }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'أكمل بيانات المريض أدناه' : 'Fill in the patient information below' }}</p>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Main Fields -->
            <div class="md:col-span-2 lg:col-span-2 space-y-6">
                <!-- Personal Information -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'المعلومات الشخصية' : 'Personal Information' }}</h2>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'الاسم الكامل' : 'Full Name' }} <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.full_name"
                                type="text"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                :placeholder="isRtl ? 'أدخل اسم المريض الكامل' : 'Enter patient full name'"
                            />
                            <p v-if="form.errors.full_name" class="mt-1.5 text-xs text-red-600">{{ form.errors.full_name }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'الهاتف' : 'Phone' }} <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                    :placeholder="isRtl ? 'الهاتف الرئيسي' : 'Primary phone'"
                                />
                                <p v-if="form.errors.phone" class="mt-1.5 text-xs text-red-600">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'الهاتف 2' : 'Phone 2' }}</label>
                                <input
                                    v-model="form.phone2"
                                    type="text"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                    :placeholder="isRtl ? 'الهاتف الثانوي (اختياري)' : 'Secondary phone (optional)'"
                                />
                                <p v-if="form.errors.phone2" class="mt-1.5 text-xs text-red-600">{{ form.errors.phone2 }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'البريد' : 'Email' }}</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                    :placeholder="isRtl ? 'البريد الإلكتروني' : 'Email address'"
                                />
                                <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }}</label>
                                <input
                                    v-model="form.date_of_birth"
                                    type="date"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'الجنس' : 'Gender' }} <span class="text-red-500">*</span></label>
                                <select
                                    v-model="form.gender"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                >
                                    <option value="female">{{ isRtl ? 'أنثى' : 'Female' }}</option>
                                    <option value="male">{{ isRtl ? 'ذكر' : 'Male' }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'الجنسية' : 'Nationality' }}</label>
                                <select
                                    v-model="form.nationality"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                >
                                    <option v-for="nat in nationalityOptions" :key="nat.value" :value="nat.value">
                                        {{ isRtl ? nat.ar : nat.en }}
                                    </option>
                                </select>
                                <p v-if="form.errors.nationality" class="mt-1.5 text-xs text-red-600">{{ form.errors.nationality }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'العنوان' : 'Address' }}</label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                    :placeholder="isRtl ? 'عنوان المريض' : 'Patient address'"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'المهنة' : 'Occupation' }}</label>
                                <input
                                    v-model="form.occupation"
                                    type="text"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                    :placeholder="isRtl ? 'مهنة المريض' : 'Patient occupation'"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medical Notes -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                        <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'ملاحظات طبية' : 'Medical Notes' }}</h2>
                    </div>
                    <textarea
                        v-model="form.medical_notes"
                        rows="4"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition resize-none"
                        :placeholder="isRtl ? 'الحساسية، الأمراض المزمنة، الأدوية الحالية...' : 'Allergies, chronic conditions, current medications, etc.'"
                    ></textarea>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Referral -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 rounded-xl bg-cyan-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                        </div>
                        <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'الإحالة' : 'Referral' }}</h2>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'مصدر الإحالة' : 'Referral Source' }}</label>
                            <select
                                v-model="form.referral_source"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                            >
                                <option v-for="opt in referralOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>

                        <div v-if="form.referral_source === 'friend' || form.referral_source === 'doctor'">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'مُحال من' : 'Referred By' }}</label>
                            <input
                                v-model="form.referred_by"
                                type="text"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 focus:bg-white transition"
                                :placeholder="isRtl ? 'اسم المُحيل' : 'Name of referrer'"
                            />
                        </div>
                    </div>
                </div>

                <!-- Photo -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'الصورة' : 'Photo' }}</h2>
                    </div>
                    <input
                        type="file"
                        accept="image/*"
                        @input="form.photo = $event.target.files[0]"
                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-600 hover:file:bg-teal-100 file:cursor-pointer file:transition-colors"
                    />
                    <p v-if="form.errors.photo" class="mt-1.5 text-xs text-red-600">{{ form.errors.photo }}</p>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 shadow-md shadow-teal-500/20 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="form.processing" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ isRtl ? 'جاري الحفظ...' : 'Saving...' }}
                    </span>
                    <span v-else>{{ isRtl ? 'إنشاء مريض' : 'Create Patient' }}</span>
                </button>
            </div>
        </form>
    </div>
</template>
