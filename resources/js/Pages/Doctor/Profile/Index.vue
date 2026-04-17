<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency } = useCurrency();

const props = defineProps({ doctor: Object });

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
const activeTab = ref('profile');

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

// Photo upload
const photoPreview = ref(null);
const photoInput = ref(null);

function onPhotoSelected(e) {
    const file = e.target.files[0];
    if (file) {
        profileForm.photo = file;
        const reader = new FileReader();
        reader.onload = (ev) => { photoPreview.value = ev.target.result; };
        reader.readAsDataURL(file);
    }
}

function triggerPhotoUpload() {
    photoInput.value?.click();
}

const profileForm = useForm({
    phone: props.doctor.phone || '',
    email: props.doctor.email || '',
    bio_en: props.doctor.bio_en || '',
    bio_ar: props.doctor.bio_ar || '',
    clinic_notes: props.doctor.clinic_notes || '',
    photo: null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updateProfile() {
    profileForm.post('/doctor/profile/update', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            profileForm.photo = null;
        },
    });
}

function updatePassword() {
    passwordForm.post('/doctor/profile/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

const dayNames = computed(() => isRtl.value
    ? ['أحد', 'اثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت']
    : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
);

const displayPhoto = computed(() => photoPreview.value || props.doctor.photo_url);
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <!-- Decorative -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-[#C4A265]/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-[#1B365D]/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10 p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <!-- Photo with upload -->
                    <div class="relative group">
                        <div class="w-24 h-24 rounded-2xl overflow-hidden ring-4 ring-[#C4A265]/30 shadow-xl shadow-[#C4A265]/10">
                            <img v-if="displayPhoto" :src="displayPhoto" :alt="doctor.name_en" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center text-white text-3xl font-bold">
                                {{ doctor.name_en?.charAt(0) || 'D' }}
                            </div>
                        </div>
                        <button
                            @click="triggerPhotoUpload"
                            class="absolute inset-0 rounded-2xl bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                        >
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                        <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhotoSelected" />
                    </div>

                    <!-- Info -->
                    <div class="text-center sm:text-start flex-1">
                        <h1 class="text-2xl font-bold text-white">Dr. {{ isRtl ? doctor.name_ar : doctor.name_en }}</h1>
                        <p v-if="doctor.name_ar && !isRtl" class="text-sm text-white/40 mt-0.5">{{ doctor.name_ar }}</p>
                        <p v-if="doctor.name_en && isRtl" class="text-sm text-white/40 mt-0.5">{{ doctor.name_en }}</p>
                        <p class="text-sm text-[#C4A265] mt-1">{{ isRtl ? (doctor.specialization_ar || doctor.specialization_en) : (doctor.specialization_en || doctor.specialization_ar) || $t('a_specialization') }}</p>

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 mt-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold"
                                :class="doctor.status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-gray-500/20 text-gray-400'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="doctor.status === 'active' ? 'bg-emerald-400' : 'bg-gray-400'"></span>
                                {{ doctor.status === 'active' ? (isRtl ? 'نشط' : 'Active') : (isRtl ? 'غير نشط' : 'Inactive') }}
                            </span>
                            <span v-if="doctor.phone" class="inline-flex items-center gap-1.5 text-xs text-white/50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                {{ doctor.phone }}
                            </span>
                            <span v-if="doctor.email" class="inline-flex items-center gap-1.5 text-xs text-white/50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                {{ doctor.email }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mt-4 sm:mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ $t('a_consultation_fees') }}</p>
                        <p class="text-lg font-bold text-white mt-0.5">{{ formatCurrency(doctor.consultation_fee || 0) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ $t('a_commission_rates') }}</p>
                        <p class="text-lg font-bold text-[#C4A265] mt-0.5">{{ doctor.default_commission_percentage || 0 }}%</p>
                    </div>
                    <div v-if="doctor.dermatology_fee" class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ $t('a_dermatology_fee') }}</p>
                        <p class="text-lg font-bold text-white mt-0.5">{{ formatCurrency(doctor.dermatology_fee) }}</p>
                    </div>
                    <div v-if="doctor.cosmetic_fee" class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ $t('a_cosmetic_fee') }}</p>
                        <p class="text-lg font-bold text-white mt-0.5">{{ formatCurrency(doctor.cosmetic_fee) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div
            class="flex gap-1 bg-white rounded-xl p-1 shadow-sm border border-gray-100 transition-all duration-500 overflow-x-auto scrollbar-none"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <button
                @click="activeTab = 'profile'"
                class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
                :class="activeTab === 'profile' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
            >
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    {{ $t('a_edit_profile') }}
                </span>
            </button>
            <button
                @click="activeTab = 'password'"
                class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
                :class="activeTab === 'password' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
            >
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    {{ $t('a_change_password') }}
                </span>
            </button>
            <button
                @click="activeTab = 'info'"
                class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
                :class="activeTab === 'info' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
            >
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ isRtl ? 'معلوماتي' : 'My Info' }}
                </span>
            </button>
        </div>

        <!-- Content -->
        <div
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <!-- Profile Edit Tab -->
            <div v-if="activeTab === 'profile'">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="border-b border-gray-100 px-4 sm:px-6 py-4">
                        <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            {{ $t('a_edit_profile') }}
                        </h3>
                    </div>
                    <form @submit.prevent="updateProfile" class="p-4 sm:p-6 space-y-5">
                        <!-- Photo indicator -->
                        <div v-if="photoPreview" class="flex items-center gap-3 p-3 bg-amber-50 rounded-xl border border-amber-200">
                            <img :src="photoPreview" class="w-10 h-10 rounded-lg object-cover" />
                            <div class="flex-1">
                                <p class="text-sm font-medium text-amber-800">{{ isRtl ? 'صورة جديدة محددة' : 'New photo selected' }}</p>
                                <p class="text-xs text-amber-600">{{ isRtl ? 'سيتم رفعها عند حفظ الملف' : 'Will be uploaded when you save' }}</p>
                            </div>
                            <button type="button" @click="photoPreview = null; profileForm.photo = null" class="text-amber-500 hover:text-amber-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $t('a_phone') }}</label>
                                <input v-model="profileForm.phone" type="text" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition" />
                                <p v-if="profileForm.errors.phone" class="text-xs text-red-500 mt-1">{{ profileForm.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $t('a_email') }}</label>
                                <input v-model="profileForm.email" type="email" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition" />
                                <p v-if="profileForm.errors.email" class="text-xs text-red-500 mt-1">{{ profileForm.errors.email }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $t('a_bio_en') }}</label>
                            <textarea v-model="profileForm.bio_en" rows="3" dir="ltr" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $t('a_bio_ar') }}</label>
                            <textarea v-model="profileForm.bio_ar" rows="3" dir="rtl" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $t('a_clinic_notes') }}</label>
                            <textarea v-model="profileForm.clinic_notes" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition"></textarea>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100">
                            <button type="button" @click="triggerPhotoUpload" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                {{ $t('a_upload_photo') }}
                            </button>
                            <button type="submit" :disabled="profileForm.processing" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-[#C4A265] to-[#D4B87A] hover:from-[#B89255] hover:to-[#C4A265] rounded-xl shadow-md shadow-[#C4A265]/20 transition-all disabled:opacity-50">
                                <svg v-if="profileForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                {{ profileForm.processing ? $t('a_saving') : $t('a_update_profile') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Tab -->
            <div v-if="activeTab === 'password'">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden max-w-lg mx-auto">
                    <div class="border-b border-gray-100 px-4 sm:px-6 py-4">
                        <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            {{ $t('a_change_password') }}
                        </h3>
                    </div>
                    <form @submit.prevent="updatePassword" class="p-4 sm:p-6 space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $t('a_current_password') }}</label>
                            <input v-model="passwordForm.current_password" type="password" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition" required />
                            <p v-if="passwordForm.errors.current_password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $t('a_new_password') }}</label>
                                <input v-model="passwordForm.password" type="password" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition" required />
                                <p v-if="passwordForm.errors.password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $t('a_confirm_password') }}</label>
                                <input v-model="passwordForm.password_confirmation" type="password" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition" required />
                            </div>
                        </div>
                        <div class="flex justify-end pt-2 border-t border-gray-100">
                            <button type="submit" :disabled="passwordForm.processing" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-xl shadow-md transition-all disabled:opacity-50">
                                <svg v-if="passwordForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                {{ passwordForm.processing ? $t('a_changing_password') : $t('a_change_password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Tab -->
            <div v-if="activeTab === 'info'">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    <!-- Consultation Fees Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800">{{ $t('a_consultation_fees') }}</h3>
                        </div>
                        <div class="space-y-3">
                            <div v-if="doctor.dermatology_fee" class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_dermatology_fee') }}</span>
                                <span class="text-sm font-bold text-gray-800">{{ formatCurrency(doctor.dermatology_fee) }}</span>
                            </div>
                            <div v-if="doctor.cosmetic_fee" class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_cosmetic_fee') }}</span>
                                <span class="text-sm font-bold text-gray-800">{{ formatCurrency(doctor.cosmetic_fee) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm text-gray-500">{{ $t('a_general_fee') }}</span>
                                <span class="text-sm font-bold text-gray-800">{{ formatCurrency(doctor.consultation_fee) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Commission Rates Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800">{{ $t('a_commission_rates') }}</h3>
                        </div>
                        <div class="space-y-3">
                            <div v-if="doctor.dermatology_commission" class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_dermatology') }}</span>
                                <span class="text-sm font-bold text-[#C4A265]">{{ doctor.dermatology_commission }}%</span>
                            </div>
                            <div v-if="doctor.cosmetic_commission" class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_cosmetic') }}</span>
                                <span class="text-sm font-bold text-[#C4A265]">{{ doctor.cosmetic_commission }}%</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm text-gray-500">{{ $t('a_default_commission') }}</span>
                                <span class="text-sm font-bold text-[#C4A265]">{{ doctor.default_commission_percentage || 0 }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800">{{ $t('a_schedule') }}</h3>
                        </div>
                        <div v-if="doctor.schedules?.length > 0" class="space-y-2">
                            <div v-for="s in doctor.schedules" :key="s.id" class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                                <span class="text-sm font-medium text-gray-600">{{ dayNames[s.day_of_week] }}</span>
                                <span class="text-sm text-gray-800 font-mono">{{ s.start_time?.slice(0,5) }} - {{ s.end_time?.slice(0,5) }}</span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا يوجد جدول محدد' : 'No schedule set' }}</p>
                    </div>

                    <!-- Custom Service Rates Card -->
                    <div v-if="doctor.service_rates?.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 md:col-span-2 lg:col-span-3">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800">{{ $t('a_custom_service_rates') }}</h3>
                                <p class="text-xs text-gray-400">{{ $t('a_custom_rates_note') }}</p>
                            </div>
                        </div>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-4">
                            <div v-for="sr in doctor.service_rates" :key="sr.id" class="flex justify-between items-center px-4 py-3 bg-gray-50 rounded-xl">
                                <span class="text-sm text-gray-600 truncate flex-1">{{ isRtl ? (sr.service?.name_ar || sr.service?.name_en) : (sr.service?.name_en || sr.service?.name_ar) }}</span>
                                <span class="text-sm font-bold text-[#C4A265] whitespace-nowrap ltr:ml-3 rtl:mr-3">{{ sr.commission_percentage }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
