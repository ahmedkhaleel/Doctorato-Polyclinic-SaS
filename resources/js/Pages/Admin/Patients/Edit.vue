<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    patient: Object,
});

const form = useForm({
    _method: 'PUT',
    full_name: props.patient.full_name || '',
    phone: props.patient.phone || '',
    phone2: props.patient.phone2 || '',
    email: props.patient.email || '',
    date_of_birth: props.patient.date_of_birth || '',
    gender: props.patient.gender || 'female',
    blood_type: props.patient.blood_type || '',
    marital_status: props.patient.marital_status || '',
    nationality: props.patient.nationality || '',
    address: props.patient.address || '',
    occupation: props.patient.occupation || '',
    emergency_contact_name: props.patient.emergency_contact_name || '',
    emergency_contact_phone: props.patient.emergency_contact_phone || '',
    allergies: props.patient.allergies || '',
    chronic_conditions: props.patient.chronic_conditions || '',
    current_medications: props.patient.current_medications || '',
    referral_source: props.patient.referral_source || 'walk_in',
    referred_by: props.patient.referred_by || '',
    medical_notes: props.patient.medical_notes || '',
    is_active: props.patient.is_active ?? true,
    photo: null,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const photoPreview = ref(null);
const clientErrors = ref({});

function onPhotoChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
}

function removePhoto() {
    form.photo = null;
    photoPreview.value = null;
}

function validatePhone(value) {
    if (!value) return true;
    return /^[0-9+\-\s]+$/.test(value);
}

function validateEmail(value) {
    if (!value) return true;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function validateForm() {
    const errors = {};

    if (!form.full_name.trim()) {
        errors.full_name = 'Full name is required.';
    }

    if (!form.phone.trim()) {
        errors.phone = 'Phone number is required.';
    } else if (!validatePhone(form.phone)) {
        errors.phone = 'Phone must contain only numbers, +, - or spaces.';
    }

    if (form.phone2 && !validatePhone(form.phone2)) {
        errors.phone2 = 'Phone must contain only numbers, +, - or spaces.';
    }

    if (form.email && !validateEmail(form.email)) {
        errors.email = 'Please enter a valid email address.';
    }

    if (form.emergency_contact_phone && !validatePhone(form.emergency_contact_phone)) {
        errors.emergency_contact_phone = 'Phone must contain only numbers, +, - or spaces.';
    }

    clientErrors.value = errors;
    return Object.keys(errors).length === 0;
}

function filterPhoneInput(field) {
    form[field] = form[field].replace(/[^0-9+\-\s]/g, '');
}

function getError(field) {
    return form.errors[field] || clientErrors.value[field] || '';
}

function submit() {
    if (!validateForm()) return;

    form.post(`/admin/patients/${props.patient.id}`, {
        forceFormData: true,
    });
}

const referralOptions = [
    { value: 'walk_in', label: 'Walk-in', icon: 'M15 11a3 3 0 11-6 0 3 3 0 016 0zM6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2' },
    { value: 'social_media', label: 'Social Media', icon: 'M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84' },
    { value: 'google', label: 'Google', icon: 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM10 10l4 2-4 2V10z' },
    { value: 'friend', label: 'Friend', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { value: 'doctor', label: 'Doctor Referral', icon: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { value: 'advertisement', label: 'Advertisement', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
    { value: 'other', label: 'Other', icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
];

const bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
const maritalOptions = [
    { value: '', label: 'Not specified' },
    { value: 'single', label: 'Single' },
    { value: 'married', label: 'Married' },
    { value: 'divorced', label: 'Divorced' },
    { value: 'widowed', label: 'Widowed' },
];

const nationalities = [
    'Egyptian', 'Saudi', 'Emirati', 'Kuwaiti', 'Qatari', 'Bahraini', 'Omani', 'Yemeni',
    'Iraqi', 'Jordanian', 'Palestinian', 'Lebanese', 'Syrian', 'Libyan', 'Tunisian',
    'Algerian', 'Moroccan', 'Sudanese', 'Somali', 'Djiboutian', 'Comoran', 'Mauritanian',
    'Afghan', 'Albanian', 'American', 'Andorran', 'Angolan', 'Argentine', 'Armenian',
    'Australian', 'Austrian', 'Azerbaijani', 'Bahamian', 'Bangladeshi', 'Barbadian',
    'Belarusian', 'Belgian', 'Belizean', 'Beninese', 'Bhutanese', 'Bolivian', 'Bosnian',
    'Brazilian', 'British', 'Bruneian', 'Bulgarian', 'Burkinabe', 'Burmese', 'Burundian',
    'Cambodian', 'Cameroonian', 'Canadian', 'Cape Verdean', 'Central African', 'Chadian',
    'Chilean', 'Chinese', 'Colombian', 'Congolese', 'Costa Rican', 'Croatian', 'Cuban',
    'Cypriot', 'Czech', 'Danish', 'Dominican', 'Dutch', 'Ecuadorian', 'Equatorial Guinean',
    'Eritrean', 'Estonian', 'Ethiopian', 'Fijian', 'Filipino', 'Finnish', 'French',
    'Gabonese', 'Gambian', 'Georgian', 'German', 'Ghanaian', 'Greek', 'Grenadian',
    'Guatemalan', 'Guinean', 'Guyanese', 'Haitian', 'Honduran', 'Hungarian', 'Icelandic',
    'Indian', 'Indonesian', 'Iranian', 'Irish', 'Israeli', 'Italian', 'Ivorian',
    'Jamaican', 'Japanese', 'Kazakh', 'Kenyan', 'Kyrgyz', 'Lao', 'Latvian', 'Liberian',
    'Lithuanian', 'Luxembourgish', 'Macedonian', 'Malagasy', 'Malawian', 'Malaysian',
    'Maldivian', 'Malian', 'Maltese', 'Mauritian', 'Mexican', 'Moldovan', 'Mongolian',
    'Montenegrin', 'Mozambican', 'Namibian', 'Nepalese', 'New Zealander', 'Nicaraguan',
    'Nigerian', 'Nigerien', 'North Korean', 'Norwegian', 'Pakistani', 'Panamanian',
    'Paraguayan', 'Peruvian', 'Polish', 'Portuguese', 'Romanian', 'Russian', 'Rwandan',
    'Saint Lucian', 'Salvadoran', 'Senegalese', 'Serbian', 'Sierra Leonean', 'Singaporean',
    'Slovak', 'Slovenian', 'South African', 'South Korean', 'Spanish', 'Sri Lankan',
    'Surinamese', 'Swedish', 'Swiss', 'Taiwanese', 'Tajik', 'Tanzanian', 'Thai',
    'Togolese', 'Trinidadian', 'Turkish', 'Turkmen', 'Ugandan', 'Ukrainian', 'Uruguayan',
    'Uzbek', 'Venezuelan', 'Vietnamese', 'Zambian', 'Zimbabwean',
];

const currentPhotoUrl = props.patient.photo
    ? (props.patient.photo_url)
    : null;
</script>

<template>
    <AdminLayout :title="$t('a_edit_patient')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="`/admin/patients/${patient.id}`" class="flex items-center justify-center w-10 h-10 rounded-xl bg-white shadow-sm border border-gray-200 text-gray-500 hover:text-gray-800 hover:border-gray-300 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_edit_patient') }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ patient.full_name }}</p>
                    </div>
                </div>
                <span class="text-sm font-mono px-4 py-1.5 rounded-xl bg-amber-50 border border-amber-100 font-semibold" style="color: #C4A265;">{{ patient.file_number }}</span>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Fields -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Personal Information -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: rgba(196, 162, 101, 0.1);">
                                <svg class="w-5 h-5" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-gray-800">{{ $t('a_personal_info') }}</h2>
                                <p class="text-xs text-gray-400">{{ $t('a_basic_patient_details') }}</p>
                            </div>
                        </div>

                        <div class="p-4 md:p-6 space-y-5">
                            <!-- Full Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_full_name') }} <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <input v-model="form.full_name" type="text"
                                        class="doctorato-input w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm transition-all duration-200"
                                        :class="getError('full_name') ? 'border-red-300 bg-red-50 focus:ring-[#C4A265]/30 focus:border-red-400' : 'border-gray-200 bg-gray-50/50 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300'" />
                                </div>
                                <p v-if="getError('full_name')" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    {{ getError('full_name') }}
                                </p>
                            </div>

                            <!-- Phone Numbers -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_phone') }} <span class="text-red-400">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </div>
                                        <input v-model="form.phone" type="tel" dir="ltr" @input="filterPhoneInput('phone')"
                                            class="doctorato-input w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm transition-all duration-200"
                                            :class="getError('phone') ? 'border-red-300 bg-red-50 focus:ring-[#C4A265]/30 focus:border-red-400' : 'border-gray-200 bg-gray-50/50 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300'" />
                                    </div>
                                    <p v-if="getError('phone')" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ getError('phone') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_phone2') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </div>
                                        <input v-model="form.phone2" type="tel" dir="ltr" @input="filterPhoneInput('phone2')"
                                            class="doctorato-input w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm transition-all duration-200"
                                            :class="getError('phone2') ? 'border-red-300 bg-red-50 focus:ring-[#C4A265]/30 focus:border-red-400' : 'border-gray-200 bg-gray-50/50 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300'" />
                                    </div>
                                    <p v-if="getError('phone2')" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ getError('phone2') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Email & DOB -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_email') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <input v-model="form.email" type="email"
                                            class="doctorato-input w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm transition-all duration-200"
                                            :class="getError('email') ? 'border-red-300 bg-red-50 focus:ring-[#C4A265]/30 focus:border-red-400' : 'border-gray-200 bg-gray-50/50 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300'" />
                                    </div>
                                    <p v-if="getError('email')" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ getError('email') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_date_of_birth') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <input v-model="form.date_of_birth" type="date"
                                            class="doctorato-input w-full pl-10 pr-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200" />
                                    </div>
                                </div>
                            </div>

                            <!-- Gender, Blood Type, Marital -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_gender') }} <span class="text-red-400">*</span></label>
                                    <div class="flex gap-2">
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" v-model="form.gender" value="female" class="sr-only peer" />
                                            <div class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl border text-sm font-medium transition-all peer-checked:border-amber-300 peer-checked:bg-amber-50 peer-checked:text-[#C4A265] border-gray-200 text-gray-500 hover:border-gray-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke-width="1.5"/><path stroke-linecap="round" stroke-width="1.5" d="M12 12v8m-3-3h6"/></svg>
                                                {{ $t('a_female') }}
                                            </div>
                                        </label>
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" v-model="form.gender" value="male" class="sr-only peer" />
                                            <div class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl border text-sm font-medium transition-all peer-checked:border-slate-300 peer-checked:bg-slate-50 peer-checked:text-[#1B365D] border-gray-200 text-gray-500 hover:border-gray-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="10" cy="14" r="4" stroke-width="1.5"/><path stroke-linecap="round" stroke-width="1.5" d="M14 10l6-6m0 0h-5m5 0v5"/></svg>
                                                {{ $t('a_male') }}
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_blood_type') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                        </div>
                                        <select v-model="form.blood_type"
                                            class="doctorato-input w-full pl-10 pr-9 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 appearance-none">
                                            <option value="">Not specified</option>
                                            <option v-for="bt in bloodTypes" :key="bt" :value="bt">{{ bt }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_marital_status') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                        </div>
                                        <select v-model="form.marital_status"
                                            class="doctorato-input w-full pl-10 pr-9 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 appearance-none">
                                            <option v-for="ms in maritalOptions" :key="ms.value" :value="ms.value">{{ ms.label }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Nationality, Occupation -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_nationality') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <select v-model="form.nationality"
                                            class="doctorato-input w-full pl-10 pr-9 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 appearance-none">
                                            <option value="">Not specified</option>
                                            <option v-for="nat in nationalities" :key="nat" :value="nat">{{ nat }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_occupation') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <input v-model="form.occupation" type="text"
                                            class="doctorato-input w-full pl-10 pr-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200" />
                                    </div>
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_address') }}</label>
                                <div class="relative">
                                    <div class="absolute top-3 left-3.5 pointer-events-none">
                                        <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <input v-model="form.address" type="text"
                                        class="doctorato-input w-full pl-10 pr-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636a9 9 0 11-12.728 0M12 9v4m0 4h.01"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-gray-800">{{ $t('a_emergency_contact') }}</h2>
                                <p class="text-xs text-gray-400">{{ $t('a_in_case_of_emergency') }}</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_contact_name') }}</label>
                                    <input v-model="form.emergency_contact_name" type="text"
                                        class="doctorato-input w-full px-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_contact_phone') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </div>
                                        <input v-model="form.emergency_contact_phone" type="tel" dir="ltr" @input="filterPhoneInput('emergency_contact_phone')"
                                            class="doctorato-input w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm transition-all duration-200"
                                            :class="getError('emergency_contact_phone') ? 'border-red-300 bg-red-50 focus:ring-[#C4A265]/30 focus:border-red-400' : 'border-gray-200 bg-gray-50/50 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300'" />
                                    </div>
                                    <p v-if="getError('emergency_contact_phone')" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ getError('emergency_contact_phone') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Information -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-gray-800">{{ $t('a_medical_info') }}</h2>
                                <p class="text-xs text-gray-400">{{ $t('a_important_medical_history') }}</p>
                            </div>
                        </div>
                        <div class="p-4 md:p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_allergies') }}</label>
                                <textarea v-model="form.allergies" rows="2" placeholder="Drug allergies, food allergies, etc."
                                    class="doctorato-input w-full px-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 resize-none"></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_chronic_conditions') }}</label>
                                    <textarea v-model="form.chronic_conditions" rows="2" placeholder="Diabetes, hypertension, etc."
                                        class="doctorato-input w-full px-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 resize-none"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_current_medications') }}</label>
                                    <textarea v-model="form.current_medications" rows="2" placeholder="List current medications"
                                        class="doctorato-input w-full px-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 resize-none"></textarea>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_additional_medical_notes') }}</label>
                                <textarea v-model="form.medical_notes" rows="3" placeholder="Any additional notes relevant to treatment..."
                                    class="doctorato-input w-full px-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 resize-none"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-800">{{ $t('a_status') }}</h2>
                        </div>
                        <div class="p-6">
                            <label class="flex items-center justify-between cursor-pointer">
                                <span class="text-sm font-medium text-gray-700">{{ $t('a_active_patient') }}</span>
                                <div class="relative">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-emerald-500 transition-colors duration-200"></div>
                                    <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-5"></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Photo Upload -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-800">{{ $t('a_patient_photo') }}</h2>
                        </div>
                        <div class="p-6">
                            <!-- Current or preview photo -->
                            <div v-if="photoPreview || currentPhotoUrl" class="mb-4 text-center">
                                <div class="relative inline-block">
                                    <img :src="photoPreview || currentPhotoUrl" class="w-28 h-28 rounded-2xl object-cover shadow-md border-2 border-white" alt="" />
                                    <button v-if="photoPreview" type="button" @click="removePhoto" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <p v-if="!photoPreview && currentPhotoUrl" class="text-xs text-gray-400 mt-2">{{ $t('a_current_photo') }}</p>
                            </div>
                            <label class="group cursor-pointer block">
                                <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 md:p-6 text-center hover:border-amber-300 hover:bg-amber-50/30 transition-all duration-200">
                                    <svg class="w-8 h-8 mx-auto text-gray-300 group-hover:text-amber-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <p class="mt-2 text-xs text-gray-500">{{ $t('a_click_to_upload_photo') }}</p>
                                    <p class="text-xs text-gray-400">JPG, PNG or WebP (max 5MB)</p>
                                </div>
                                <input type="file" accept="image/jpeg,image/png,image/webp" @change="onPhotoChange" class="hidden" />
                            </label>
                            <p v-if="form.errors.photo" class="mt-2 text-xs text-red-500">{{ form.errors.photo }}</p>
                        </div>
                    </div>

                    <!-- Referral -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: rgba(196, 162, 101, 0.1);">
                                <svg class="w-5 h-5" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-800">{{ $t('a_referral_source') }}</h2>
                        </div>
                        <div class="p-4 md:p-6 space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="opt in referralOptions" :key="opt.value" class="cursor-pointer">
                                    <input type="radio" v-model="form.referral_source" :value="opt.value" class="sr-only peer" />
                                    <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-medium transition-all peer-checked:shadow-sm border-gray-200 text-gray-500 hover:border-gray-300"
                                         :class="form.referral_source === opt.value ? 'border-amber-300 bg-amber-50 text-amber-800' : ''">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="opt.icon"/></svg>
                                        <span class="truncate">{{ opt.label }}</span>
                                    </div>
                                </label>
                            </div>

                            <div v-if="form.referral_source === 'friend' || form.referral_source === 'doctor'" class="pt-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_referred_by') }}</label>
                                <input v-model="form.referred_by" type="text"
                                    class="doctorato-input w-full px-4 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200" />
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 px-4 md:px-6 py-3.5 rounded-xl text-white font-semibold text-sm transition-all duration-200 shadow-lg shadow-amber-500/20 hover:shadow-xl hover:shadow-amber-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background-color: #C4A265;">
                            <svg v-if="!form.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ form.processing ? $t('a_saving') : $t('a_update_patient') }}
                        </button>
                        <Link :href="`/admin/patients/${patient.id}`"
                            class="w-full flex items-center justify-center gap-2 px-4 md:px-6 py-3 rounded-xl text-gray-600 font-medium text-sm border border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
