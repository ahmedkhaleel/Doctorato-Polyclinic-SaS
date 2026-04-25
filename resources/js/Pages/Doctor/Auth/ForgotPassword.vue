<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const form = useForm({ email: '' });
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const flashSuccess = computed(() => page.props.flash?.success);
const flashError   = computed(() => page.props.flash?.error);

function submit() { form.post('/doctor/forgot-password', { preserveScroll: true }); }
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'"
         class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-[#1a1a2e] via-[#16213e] to-[#1a1a2e]"
         :style="{ fontFamily: isRtl ? '\'Tajawal\', sans-serif' : '\'Poppins\', sans-serif' }">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <img src="/images/logo/logo-light.png" alt="Doctorato" class="mx-auto h-10 w-auto mb-3" />
                <p class="text-gray-400 text-sm uppercase tracking-widest">{{ isRtl ? 'بوابة الطبيب' : 'Doctor Portal' }}</p>
            </div>

            <div class="bg-white/5 backdrop-blur-md rounded-2xl border border-white/10 shadow-2xl p-7">
                <h1 class="text-white text-xl font-bold mb-2">{{ isRtl ? 'استعادة كلمة المرور' : 'Forgot password?' }}</h1>
                <p class="text-gray-400 text-sm mb-6">{{ isRtl ? 'أدخل بريدك وسنرسل رابط إعادة التعيين.' : 'Enter your email — we\'ll send a reset link.' }}</p>

                <div v-if="flashSuccess" class="mb-4 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ flashSuccess }}</div>
                <div v-if="flashError" class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm">{{ flashError }}</div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">{{ isRtl ? 'البريد الإلكتروني' : 'Email' }}</label>
                        <input v-model="form.email" type="email" required autocomplete="email"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-[#C4A265] focus:ring-2 focus:ring-[#C4A265]/30 outline-none transition" />
                        <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <button type="submit" :disabled="form.processing"
                        class="w-full py-2.5 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#D9B985] text-[#1B365D] font-bold disabled:opacity-50">
                        {{ form.processing ? (isRtl ? 'جارٍ الإرسال...' : 'Sending...') : (isRtl ? 'إرسال رابط إعادة التعيين' : 'Send Reset Link') }}
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <Link href="/doctor/login" class="text-sm text-gray-400 hover:text-[#C4A265]">← {{ isRtl ? 'العودة لتسجيل الدخول' : 'Back to login' }}</Link>
                </div>
            </div>
        </div>
    </div>
</template>
