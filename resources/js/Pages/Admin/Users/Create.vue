<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    roles: Array,
});

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: props.roles.length > 0 ? props.roles[0].id : '',
    is_active: true,
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const passwordStrength = ref(0);

const selectedRole = computed(() => {
    return props.roles.find(r => r.id === form.role_id);
});

const initials = computed(() => {
    const parts = form.name.trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return form.name ? form.name.substring(0, 2).toUpperCase() : '??';
});

const roleColor = computed(() => {
    const colors = {
        super_admin: { bg: 'bg-amber-100', text: 'text-amber-700', ring: 'ring-amber-200' },
        doctor: { bg: 'bg-slate-100', text: 'text-[#1B365D]', ring: 'ring-slate-200' },
        secretary: { bg: 'bg-slate-100', text: 'text-[#1B365D]', ring: 'ring-slate-200' },
        receptionist: { bg: 'bg-teal-100', text: 'text-teal-700', ring: 'ring-teal-200' },
        accountant: { bg: 'bg-emerald-100', text: 'text-emerald-700', ring: 'ring-emerald-200' },
        editor: { bg: 'bg-amber-100', text: 'text-[#C4A265]', ring: 'ring-amber-200' },
        moderator: { bg: 'bg-amber-100', text: 'text-[#C4A265]', ring: 'ring-amber-200' },
        webmaster: { bg: 'bg-slate-100', text: 'text-[#1B365D]', ring: 'ring-slate-200' },
    };
    return colors[selectedRole.value?.name] || { bg: 'bg-gray-100', text: 'text-gray-700', ring: 'ring-gray-200' };
});

function calcPasswordStrength(pass) {
    if (!pass) return 0;
    let score = 0;
    if (pass.length >= 8) score++;
    if (pass.length >= 12) score++;
    if (/[A-Z]/.test(pass)) score++;
    if (/[0-9]/.test(pass)) score++;
    if (/[^A-Za-z0-9]/.test(pass)) score++;
    return Math.min(score, 5);
}

const strengthLabel = computed(() => {
    const labels = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
    return labels[passwordStrength.value] || '';
});

const strengthColor = computed(() => {
    const colors = ['', 'bg-red-500', 'bg-[#C4A265]', 'bg-amber-500', 'bg-[#1B365D]', 'bg-emerald-500'];
    return colors[passwordStrength.value] || '';
});

const passwordsMatch = computed(() => {
    if (!form.password || !form.password_confirmation) return null;
    return form.password === form.password_confirmation;
});

watch(() => form.password, (val) => { passwordStrength.value = calcPasswordStrength(val); });

function submit() {
    form.post('/admin/users');
}
</script>

<template>
    <AdminLayout :title="$t('a_add_user')">
        <div>
            <!-- Breadcrumb + Actions -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2 text-sm">
                    <Link href="/admin/users" class="text-gray-400 hover:text-[#C4A265] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </Link>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <Link href="/admin/users" class="text-gray-400 hover:text-[#C4A265] transition-colors">{{ $t('a_users') }}</Link>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="text-gray-700 font-medium">{{ $t('a_new_user') }}</span>
                </div>
                <Link href="/admin/users" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ $t('a_back') }}
                </Link>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- LEFT: Preview Card -->
                    <div class="lg:col-span-1 space-y-5">
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                            <div class="h-20 bg-gradient-to-br from-gray-800 via-gray-900 to-black relative">
                                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIvPjwvc3ZnPg==')] opacity-50"></div>
                            </div>
                            <div class="px-5 pb-5 -mt-10 relative">
                                <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-xl font-bold text-white shadow-lg ring-4 ring-white"
                                     :style="{ background: `linear-gradient(135deg, #C4A265, #a8884f)` }">
                                    {{ initials }}
                                </div>
                                <h2 class="mt-3 text-lg font-bold text-gray-800">{{ form.name || $t('a_new_user') }}</h2>
                                <p class="text-xs text-gray-400 truncate">{{ form.email || 'email@example.com' }}</p>
                                <div class="mt-3" v-if="selectedRole">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg ring-1"
                                          :class="[roleColor.bg, roleColor.text, roleColor.ring]">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="roleColor.text.replace('text-', 'bg-')"></span>
                                        {{ selectedRole.display_name_en }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-5">
                            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $t('a_account_status') }}</h3>
                            <button type="button" @click="form.is_active = !form.is_active"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors"
                                    :class="form.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                                <svg v-if="form.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                <span class="font-medium">{{ form.is_active ? $t('a_account_active') : $t('a_account_inactive') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- RIGHT: Form Fields -->
                    <div class="lg:col-span-2 space-y-5">
                        <!-- Basic Info -->
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                            <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800">{{ $t('a_basic_information') }}</h3>
                                    <p class="text-xs text-gray-400">{{ $t('a_user_identity_desc') }}</p>
                                </div>
                            </div>
                            <div class="p-4 md:p-6 space-y-5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $t('a_full_name') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </div>
                                        <input v-model="form.name" type="text" :placeholder="$t('a_enter_full_name')"
                                               class="doctorato-input w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm transition-all duration-200 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none"
                                               :class="form.errors.name ? 'border-red-300 bg-red-50/50' : 'border-gray-200 bg-gray-50/50 hover:border-gray-300'" />
                                    </div>
                                    <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $t('a_username') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <input v-model="form.username" type="text" :placeholder="$t('a_enter_username')"
                                               class="doctorato-input w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm transition-all duration-200 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none"
                                               :class="form.errors.username ? 'border-red-300 bg-red-50/50' : 'border-gray-200 bg-gray-50/50 hover:border-gray-300'" />
                                    </div>
                                    <p v-if="form.errors.username" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        {{ form.errors.username }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $t('a_email_address') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        </div>
                                        <input v-model="form.email" type="email" :placeholder="$t('a_email_placeholder')"
                                               class="doctorato-input w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm transition-all duration-200 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none"
                                               :class="form.errors.email ? 'border-red-300 bg-red-50/50' : 'border-gray-200 bg-gray-50/50 hover:border-gray-300'" />
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        {{ form.errors.email }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Role & Permissions -->
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                            <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800">{{ $t('a_role_access') }}</h3>
                                    <p class="text-xs text-gray-400">{{ $t('a_role_access_desc') }}</p>
                                </div>
                            </div>
                            <div class="p-6">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t('a_select_role') }}</label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    <label v-for="role in roles" :key="role.id"
                                           class="relative flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition-all duration-200 group"
                                           :class="form.role_id === role.id
                                             ? 'border-[#C4A265] bg-[#C4A265]/5 shadow-sm'
                                             : 'border-gray-100 hover:border-gray-200 bg-white'">
                                        <input type="radio" :value="role.id" v-model="form.role_id" class="sr-only" />
                                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                                             :class="form.role_id === role.id ? 'bg-[#C4A265] text-white' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold truncate" :class="form.role_id === role.id ? 'text-[#C4A265]' : 'text-gray-700'">{{ role.display_name_en }}</p>
                                            <p class="text-[10px] text-gray-400 truncate">{{ role.display_name_ar }}</p>
                                        </div>
                                        <div v-if="form.role_id === role.id" class="absolute top-1.5 right-1.5">
                                            <svg class="w-4 h-4 text-[#C4A265]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="form.errors.role_id" class="mt-2 text-xs text-red-500">{{ form.errors.role_id }}</p>
                            </div>
                        </div>

                        <!-- Security -->
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                            <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800">{{ $t('a_security') }}</h3>
                                    <p class="text-xs text-gray-400">{{ $t('a_security_desc') }}</p>
                                </div>
                            </div>
                            <div class="p-4 md:p-6 space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $t('a_password') }}</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                            </div>
                                            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" :placeholder="$t('a_min_8_chars')"
                                                   class="doctorato-input w-full pl-10 pr-10 py-2.5 border border-gray-200 bg-gray-50/50 hover:border-gray-300 rounded-xl text-sm transition-all duration-200 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none" />
                                            <button type="button" @click="showPassword = !showPassword"
                                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors" :aria-label="isRtl ? 'إظهار أو إخفاء كلمة المرور' : 'Show or hide password'" :title="isRtl ? 'إظهار أو إخفاء كلمة المرور' : 'Show or hide password'">
                                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            </button>
                                        </div>
                                        <div v-if="form.password" class="mt-2">
                                            <div class="flex gap-1 mb-1">
                                                <div v-for="i in 5" :key="i" class="h-1 flex-1 rounded-full transition-colors duration-300"
                                                     :class="i <= passwordStrength ? strengthColor : 'bg-gray-200'"></div>
                                            </div>
                                            <p class="text-[10px] font-medium" :class="{
                                                'text-red-500': passwordStrength <= 1,
                                                'text-[#C4A265]': passwordStrength === 2,
                                                'text-amber-600': passwordStrength === 3,
                                                'text-[#1B365D]': passwordStrength === 4,
                                                'text-emerald-600': passwordStrength === 5,
                                            }">{{ strengthLabel }}</p>
                                        </div>
                                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">{{ form.errors.password }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $t('a_confirm_password') }}</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <svg v-if="passwordsMatch === true" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                <svg v-else-if="passwordsMatch === false" class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                            </div>
                                            <input v-model="form.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" :placeholder="$t('a_confirm_password')"
                                                   class="doctorato-input w-full pl-10 pr-10 py-2.5 border rounded-xl text-sm transition-all duration-200 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none"
                                                   :class="passwordsMatch === false ? 'border-red-300 bg-red-50/50' : 'border-gray-200 bg-gray-50/50 hover:border-gray-300'" />
                                            <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors" :aria-label="isRtl ? 'إظهار أو إخفاء كلمة المرور' : 'Show or hide password'" :title="isRtl ? 'إظهار أو إخفاء كلمة المرور' : 'Show or hide password'">
                                                <svg v-if="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            </button>
                                        </div>
                                        <p v-if="passwordsMatch === false" class="mt-1.5 text-xs text-red-500">{{ $t('a_passwords_no_match') }}</p>
                                        <p v-else-if="passwordsMatch === true" class="mt-1.5 text-xs text-emerald-500">{{ $t('a_passwords_match') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Bar -->
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm px-4 md:px-6 py-4 flex items-center justify-end gap-3">
                            <Link href="/admin/users"
                                  class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                                {{ $t('a_cancel') }}
                            </Link>
                            <button type="submit" :disabled="form.processing"
                                    class="px-4 md:px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-all duration-200 disabled:opacity-50 shadow-sm hover:shadow-md flex items-center gap-2"
                                    style="background: linear-gradient(135deg, #C4A265, #a8884f);">
                                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                {{ form.processing ? $t('a_creating') : $t('a_create_user') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
