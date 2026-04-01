<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');

const flashError = computed(() => page.props.flash?.error);

function submit() {
    form.post('/doctor/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div :dir="dir" class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-[#1a1a2e] via-[#16213e] to-[#1a1a2e]" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="/images/logo/logo.png" alt="AURA Derma Clinic" class="mx-auto h-20 w-auto mb-3 drop-shadow-lg" />
                <h1 class="text-3xl font-bold tracking-widest text-[#C4A265]">AURA</h1>
                <p class="text-gray-400 text-sm uppercase tracking-widest mt-1">{{ isRtl ? 'بوابة الطبيب' : 'Doctor Portal' }}</p>
            </div>

            <!-- Flash Error -->
            <div v-if="flashError" class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm text-center">
                {{ flashError }}
            </div>

            <!-- Login Card -->
            <div class="bg-white/[0.05] backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/[0.08]">
                <h2 class="text-lg font-semibold text-white/90 mb-6 text-center">{{ isRtl ? 'تسجيل دخول الطبيب' : 'Doctor Login' }}</h2>

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
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[#C4A265]/50 focus:border-[#C4A265]/50 text-sm transition-all duration-200"
                            :class="form.errors.email ? 'border-red-500/50' : ''"
                            placeholder="doctor@aura-derma.com"
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
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[#C4A265]/50 focus:border-[#C4A265]/50 text-sm transition-all duration-200"
                            :class="form.errors.password ? 'border-red-500/50' : ''"
                            :placeholder="isRtl ? 'أدخل كلمة المرور' : 'Enter your password'"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-white/20 bg-white/[0.06] text-[#C4A265] focus:ring-[#C4A265]/50"
                        />
                        <label for="remember" class="ltr:ml-2 rtl:mr-2 text-sm text-white/50">{{ isRtl ? 'تذكرني' : 'Remember me' }}</label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-[#C4A265] to-[#D4B87A] hover:from-[#A68B52] hover:to-[#C4A265] transition-all duration-300 disabled:opacity-50 shadow-lg shadow-[#C4A265]/20"
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
            </div>

            <p class="text-center text-white/20 text-xs mt-6">&copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة أورا ديرما' : 'AURA Derma Clinic' }} &middot; {{ isRtl ? 'بوابة الطبيب' : 'Doctor Portal' }}</p>
        </div>
    </div>
</template>
