<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

const page = usePage();
const form = useForm({
    full_name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const flashError = computed(() => page.props.flash?.error);

function submit() {
    form.post(lp('/register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <div :dir="dir" class="min-h-screen flex items-center justify-center px-4 py-8 bg-gradient-to-br from-[#1a1a2e] via-[#16213e] to-[#1a1a2e]" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="/images/logo/logo.png" alt="Doctorato Polyclinic" class="mx-auto h-20 w-auto mb-3 drop-shadow-lg" />
                <h1 class="text-3xl font-bold tracking-widest text-[var(--brand-primary)]">Doctorato</h1>
                <p class="text-gray-400 text-sm uppercase tracking-widest mt-1">{{ isRtl ? 'بوابة المريض' : 'Patient Portal' }}</p>
            </div>

            <!-- Flash Error -->
            <div v-if="flashError" class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm text-center">
                {{ flashError }}
            </div>

            <!-- Register Card -->
            <div class="bg-white/[0.05] backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/[0.08]">
                <h2 class="text-lg font-semibold text-white/90 mb-6 text-center">{{ t('p_patient_register') }}</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-white/60 mb-1.5">{{ isRtl ? 'الاسم الكامل' : 'Full Name' }}</label>
                        <input
                            id="full_name"
                            v-model="form.full_name"
                            type="text"
                            required
                            autofocus
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 text-sm transition-all duration-200"
                            :class="form.errors.full_name ? 'border-red-500/50' : ''"
                            :placeholder="isRtl ? 'أدخل اسمك الكامل' : 'Enter your full name'"
                        />
                        <p v-if="form.errors.full_name" class="mt-1.5 text-sm text-red-400">{{ form.errors.full_name }}</p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-white/60 mb-1.5">{{ isRtl ? 'رقم الهاتف' : 'Phone Number' }}</label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            required
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 text-sm transition-all duration-200"
                            :class="form.errors.phone ? 'border-red-500/50' : ''"
                            placeholder="+966XXXXXXXXX"
                        />
                        <p class="mt-1 text-xs text-[var(--brand-primary)]/70">{{ isRtl ? 'استخدم نفس رقم الهاتف المسجل في زياراتك للعيادة' : 'Use the same phone from your clinic visits' }}</p>
                        <p v-if="form.errors.phone" class="mt-1.5 text-sm text-red-400">{{ form.errors.phone }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-white/60 mb-1.5">{{ isRtl ? 'البريد الإلكتروني' : 'Email Address' }}</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 text-sm transition-all duration-200"
                            :class="form.errors.email ? 'border-red-500/50' : ''"
                            placeholder="patient@example.com"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-400">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-white/60 mb-1.5">{{ isRtl ? 'كلمة المرور' : 'Password' }}</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 text-sm transition-all duration-200"
                            :class="form.errors.password ? 'border-red-500/50' : ''"
                            :placeholder="isRtl ? 'أدخل كلمة المرور' : 'Enter your password'"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-white/60 mb-1.5">{{ isRtl ? 'تأكيد كلمة المرور' : 'Confirm Password' }}</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 text-sm transition-all duration-200"
                            :placeholder="isRtl ? 'أعد إدخال كلمة المرور' : 'Re-enter your password'"
                        />
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:from-[var(--brand-primary-hover)] hover:to-[var(--brand-primary)] transition-all duration-300 disabled:opacity-50 shadow-lg shadow-[var(--brand-primary)]/20"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ isRtl ? 'جاري التسجيل...' : 'Registering...' }}
                        </span>
                        <span v-else>{{ isRtl ? 'إنشاء حساب' : 'Create Account' }}</span>
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <Link :href="lp('/login')" class="text-sm text-[var(--brand-primary)] hover:text-[var(--brand-secondary)] transition-colors">
                        {{ isRtl ? 'لديك حساب بالفعل؟ سجل الدخول' : 'Already have an account? Login' }}
                    </Link>
                </div>
            </div>

            <p class="text-center text-white/20 text-xs mt-6">&copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة دكتوراتو' : 'Doctorato Polyclinic' }} &middot; {{ isRtl ? 'بوابة المريض' : 'Patient Portal' }}</p>
        </div>
    </div>
</template>
