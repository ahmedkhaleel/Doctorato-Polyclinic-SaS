<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({ doctor: Object });

const profileForm = useForm({
    phone: props.doctor.phone || '',
    email: props.doctor.email || '',
    bio_en: props.doctor.bio_en || '',
    bio_ar: props.doctor.bio_ar || '',
    clinic_notes: props.doctor.clinic_notes || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updateProfile() {
    profileForm.put('/doctor/profile');
}

function updatePassword() {
    passwordForm.put('/doctor/profile/password', {
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_profile') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'عرض وتعديل ملفك الشخصي' : 'View and update your profile' }}</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Profile Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="text-center">
                    <div v-if="doctor.photo_url" class="w-20 h-20 rounded-2xl overflow-hidden mx-auto mb-3">
                        <img :src="doctor.photo_url" :alt="doctor.name_en" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center mx-auto mb-3 text-white text-2xl font-bold">
                        {{ doctor.name_en?.charAt(0) || 'D' }}
                    </div>
                    <h2 class="text-lg font-bold text-gray-800">Dr. {{ doctor.name_en }}</h2>
                    <p v-if="doctor.name_ar" class="text-sm text-gray-400">{{ doctor.name_ar }}</p>
                    <p class="text-sm text-[#C4A265] mt-1">{{ doctor.specialization_en || 'Doctor' }}</p>
                </div>

                <div class="mt-6 space-y-3 text-sm border-t border-gray-100 pt-4">
                    <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الحالة' : 'Status' }}</span>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" :class="doctor.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'">
                            {{ doctor.status }}
                        </span>
                    </div>
                </div>

                <!-- Consultation Fees -->
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'رسوم الاستشارة' : 'Consultation Fees' }}</h3>
                    <div class="space-y-2 text-sm">
                        <div v-if="doctor.dermatology_fee" class="flex justify-between">
                            <span class="text-gray-500">كشف جلدية</span>
                            <span class="font-semibold text-gray-800">{{ formatCurrency(doctor.dermatology_fee) }}</span>
                        </div>
                        <div v-if="doctor.cosmetic_fee" class="flex justify-between">
                            <span class="text-gray-500">كشف تجميل</span>
                            <span class="font-semibold text-gray-800">{{ formatCurrency(doctor.cosmetic_fee) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">General Fee</span>
                            <span class="font-semibold text-gray-800">{{ formatCurrency(doctor.consultation_fee) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Commission Rates -->
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'نسب العمولة' : 'Commission Rates' }}</h3>
                    <div class="space-y-2 text-sm">
                        <div v-if="doctor.dermatology_commission" class="flex justify-between">
                            <span class="text-gray-500">Dermatology</span>
                            <span class="font-semibold text-[#C4A265]">{{ doctor.dermatology_commission }}%</span>
                        </div>
                        <div v-if="doctor.cosmetic_commission" class="flex justify-between">
                            <span class="text-gray-500">Cosmetic</span>
                            <span class="font-semibold text-[#C4A265]">{{ doctor.cosmetic_commission }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Default</span>
                            <span class="font-semibold text-[#C4A265]">{{ doctor.default_commission_percentage || 0 }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Custom Service Rates -->
                <div v-if="doctor.service_rates?.length > 0" class="mt-6 border-t border-gray-100 pt-4">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'أسعار الخدمات المخصصة' : 'Custom Service Rates' }}</h3>
                    <p class="text-[10px] text-gray-400 mb-2">{{ isRtl ? 'هذه الأسعار تتجاوز العمولة الافتراضية لخدمات محددة' : 'These rates override the default commission for specific services' }}</p>
                    <div v-for="sr in doctor.service_rates" :key="sr.id" class="flex justify-between text-sm py-1.5 border-b border-gray-50 last:border-0">
                        <span class="text-gray-600 truncate mr-2">{{ sr.service?.name_en || sr.service?.name_ar || '-' }}</span>
                        <span class="font-bold text-[#C4A265] whitespace-nowrap">{{ sr.commission_percentage }}%</span>
                    </div>
                </div>

                <!-- Schedule -->
                <div v-if="doctor.schedules?.length > 0" class="mt-6 border-t border-gray-100 pt-4">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'الجدول' : 'Schedule' }}</h3>
                    <div v-for="s in doctor.schedules" :key="s.id" class="flex justify-between text-sm py-1">
                        <span class="text-gray-500">{{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][s.day_of_week] }}</span>
                        <span class="text-gray-800">{{ s.start_time?.slice(0,5) }} - {{ s.end_time?.slice(0,5) }}</span>
                    </div>
                </div>
            </div>

            <!-- Edit Forms -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Form -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4">{{ isRtl ? 'تعديل الملف الشخصي' : 'Edit Profile' }}</h3>
                    <form @submit.prevent="updateProfile" class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 mb-1 block">Phone</label>
                                <input v-model="profileForm.phone" type="text" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 mb-1 block">Email</label>
                                <input v-model="profileForm.email" type="email" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 mb-1 block">Bio (English)</label>
                            <textarea v-model="profileForm.bio_en" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 mb-1 block">Bio (Arabic)</label>
                            <textarea v-model="profileForm.bio_ar" rows="3" dir="rtl" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 mb-1 block">Clinic Notes</label>
                            <textarea v-model="profileForm.clinic_notes" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="profileForm.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-[#C4A265] hover:bg-[#A68B52] rounded-xl transition-colors disabled:opacity-50">
                                {{ profileForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'تحديث الملف' : 'Update Profile') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Form -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4">{{ isRtl ? 'تغيير كلمة المرور' : 'Change Password' }}</h3>
                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500 mb-1 block">Current Password</label>
                            <input v-model="passwordForm.current_password" type="password" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" required />
                            <p v-if="passwordForm.errors.current_password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 mb-1 block">New Password</label>
                                <input v-model="passwordForm.password" type="password" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" required />
                                <p v-if="passwordForm.errors.password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 mb-1 block">Confirm Password</label>
                                <input v-model="passwordForm.password_confirmation" type="password" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" required />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="passwordForm.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-xl transition-colors disabled:opacity-50">
                                {{ passwordForm.processing ? (isRtl ? 'جاري التغيير...' : 'Changing...') : (isRtl ? 'تغيير كلمة المرور' : 'Change Password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
