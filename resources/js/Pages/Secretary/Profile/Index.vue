<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ user: Object });

const profileForm = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updateProfile() {
    profileForm.put('/secretary/profile');
}

function updatePassword() {
    passwordForm.put('/secretary/profile/password', {
        onSuccess: () => passwordForm.reset(),
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'ملفي الشخصي' : 'My Profile' }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'عرض وتحديث ملفك الشخصي' : 'View and update your profile' }}</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Profile Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <div class="text-center">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center mx-auto mb-3 text-white text-2xl font-bold">
                        {{ user.name?.charAt(0)?.toUpperCase() || 'S' }}
                    </div>
                    <h2 class="text-lg font-bold text-gray-800">{{ user.name }}</h2>
                    <p class="text-sm text-teal-600 mt-1">{{ isRtl ? 'سكرتارية' : 'Secretary' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ user.email }}</p>
                </div>

                <div class="mt-6 space-y-3 text-sm border-t border-gray-100 pt-4">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'الدور' : 'Role' }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700">{{ isRtl ? 'سكرتارية' : 'Secretary' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'عضو منذ' : 'Member Since' }}</span>
                        <span class="font-medium text-gray-800">{{ formatDate(user.created_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Edit Forms -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Form -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4">{{ isRtl ? 'تعديل الملف الشخصي' : 'Edit Profile' }}</h3>
                    <form @submit.prevent="updateProfile" class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 mb-1 block">{{ isRtl ? 'الاسم' : 'Name' }}</label>
                                <input v-model="profileForm.name" type="text" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" required />
                                <p v-if="profileForm.errors.name" class="text-xs text-red-500 mt-1">{{ profileForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 mb-1 block">{{ isRtl ? 'البريد' : 'Email' }}</label>
                                <input v-model="profileForm.email" type="email" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" required />
                                <p v-if="profileForm.errors.email" class="text-xs text-red-500 mt-1">{{ profileForm.errors.email }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="profileForm.processing" class="px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 rounded-xl transition-all shadow-lg shadow-teal-500/20 disabled:opacity-50">
                                {{ profileForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'تحديث الملف الشخصي' : 'Update Profile') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Form -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4">{{ isRtl ? 'تغيير كلمة المرور' : 'Change Password' }}</h3>
                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500 mb-1 block">{{ isRtl ? 'كلمة المرور الحالية' : 'Current Password' }}</label>
                            <input v-model="passwordForm.current_password" type="password" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" required />
                            <p v-if="passwordForm.errors.current_password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 mb-1 block">{{ isRtl ? 'كلمة المرور الجديدة' : 'New Password' }}</label>
                                <input v-model="passwordForm.password" type="password" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" required />
                                <p v-if="passwordForm.errors.password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 mb-1 block">{{ isRtl ? 'تأكيد كلمة المرور' : 'Confirm Password' }}</label>
                                <input v-model="passwordForm.password_confirmation" type="password" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" required />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="passwordForm.processing" class="px-6 py-2.5 text-sm font-semibold text-white bg-gray-800 hover:bg-gray-900 rounded-xl transition-colors disabled:opacity-50">
                                {{ passwordForm.processing ? (isRtl ? 'جاري التغيير...' : 'Changing...') : (isRtl ? 'تغيير كلمة المرور' : 'Change Password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
