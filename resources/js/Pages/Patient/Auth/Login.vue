<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const showPassword = ref(false);
const showForgotMsg = ref(false);

const { lp } = usePatientLocale();

const page = usePage();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const flashError = computed(() => page.props.flash?.error);

function submit() {
    form.post(lp('/login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div :dir="dir" class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-[#1a1a2e] via-[#16213e] to-[#1a1a2e]" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="/images/logo/logo-light.png" alt="Doctorato Polyclinic" class="mx-auto h-12 w-auto mb-3 drop-shadow-lg" />
                <p class="text-gray-400 text-sm uppercase tracking-widest mt-1">{{ isRtl ? 'بوابة المريض' : 'Patient Portal' }}</p>
            </div>

            <!-- Flash Error -->
            <div v-if="flashError" class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm text-center">
                {{ flashError }}
            </div>

            <!-- Forgot Password Info -->
            <div v-if="showForgotMsg" class="mb-4 p-4 bg-[var(--brand-primary)]/10 border border-[var(--brand-primary)]/20 rounded-xl text-[var(--brand-primary)] text-sm text-center relative">
                <button @click="showForgotMsg = false" class="absolute top-2 ltr:right-2 rtl:left-2 text-[var(--brand-primary)]/50 hover:text-[var(--brand-primary)]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <p class="font-medium mb-1">{{ isRtl ? 'نسيت كلمة المرور؟' : 'Forgot your password?' }}</p>
                <p class="text-xs text-white/50">{{ isRtl ? 'يرجى التواصل مع إدارة العيادة أو الاتصال بالرقم الموجود على الموقع لإعادة تعيين كلمة المرور' : 'Please contact the clinic administration or call the number on our website to reset your password' }}</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white/[0.05] backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/[0.08]">
                <h2 class="text-lg font-semibold text-white/90 mb-6 text-center">{{ t('p_patient_login') }}</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-white/60 mb-1.5">{{ isRtl ? 'البريد الإلكتروني' : 'Email Address' }}</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 text-sm transition-all duration-200"
                            :class="form.errors.email ? 'border-red-500/50' : ''"
                            placeholder="patient@example.com"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-400">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-white/60 mb-1.5">{{ isRtl ? 'كلمة المرور' : 'Password' }}</label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                class="w-full px-4 py-3 ltr:pr-11 rtl:pl-11 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 text-sm transition-all duration-200"
                                :class="form.errors.password ? 'border-red-500/50' : ''"
                                :placeholder="isRtl ? 'أدخل كلمة المرور' : 'Enter your password'"
                            />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute top-1/2 -translate-y-1/2 ltr:right-3 rtl:left-3 text-white/30 hover:text-white/60 transition-colors"
                                :title="showPassword ? (isRtl ? 'إخفاء' : 'Hide') : (isRtl ? 'إظهار' : 'Show')">
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember Me + Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember"
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-white/20 bg-white/[0.06] text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]/50"
                            />
                            <label for="remember" class="ltr:ml-2 rtl:mr-2 text-sm text-white/50">{{ isRtl ? 'تذكرني' : 'Remember me' }}</label>
                        </div>
                        <button type="button" @click="showForgotMsg = true" class="text-sm text-[var(--brand-primary)]/70 hover:text-[var(--brand-primary)] transition-colors">
                            {{ isRtl ? 'نسيت كلمة المرور؟' : 'Forgot password?' }}
                        </button>
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
                            {{ isRtl ? 'جاري التسجيل...' : 'Logging in...' }}
                        </span>
                        <span v-else>{{ isRtl ? 'تسجيل الدخول' : 'Login' }}</span>
                    </button>
                </form>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <Link :href="lp('/register')" class="text-sm text-[var(--brand-primary)] hover:text-[var(--brand-secondary)] transition-colors">
                        {{ isRtl ? 'ليس لديك حساب؟ سجل الآن' : "Don't have an account? Register" }}
                    </Link>
                </div>
            </div>

            <p class="text-center text-white/20 text-xs mt-6">&copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة دكتوراتو' : 'Doctorato Polyclinic' }} &middot; {{ isRtl ? 'بوابة المريض' : 'Patient Portal' }}</p>
        </div>
    </div>
</template>
