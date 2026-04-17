<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const page = usePage();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const flashError = computed(() => page.props.flash?.error);

function submit() {
    form.post('/webmaster/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'" class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-[#1a0a2e] via-[#2d1b69] to-[#16213e]">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="/images/logo/logo-light.png" alt="Doctorato Polyclinic" class="mx-auto h-12 w-auto mb-3 drop-shadow-lg" />
                <p class="text-gray-400 text-sm uppercase tracking-widest mt-1">{{ isRtl ? 'بوابة مدير الموقع' : 'Webmaster Portal' }}</p>
            </div>

            <!-- Flash Error -->
            <div v-if="flashError" class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm text-center">
                {{ flashError }}
            </div>

            <!-- Login Card -->
            <div class="bg-white/[0.05] backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/[0.08]">
                <h2 class="text-lg font-semibold text-white/90 mb-6 text-center">{{ isRtl ? 'تسجيل دخول مدير الموقع' : 'Webmaster Login' }}</h2>

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
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[#1B365D]/50 focus:border-[#1B365D]/50 text-sm transition-all duration-200"
                            :class="form.errors.email ? 'border-red-500/50' : ''"
                            placeholder="webmaster@doctorato.com"
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
                            class="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.1] rounded-xl text-white placeholder-white/30 focus:ring-2 focus:ring-[#1B365D]/50 focus:border-[#1B365D]/50 text-sm transition-all duration-200"
                            :class="form.errors.password ? 'border-red-500/50' : ''"
                            placeholder="Enter your password"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-white/20 bg-white/[0.06] text-[#1B365D] focus:ring-[#1B365D]/50"
                        />
                        <label for="remember" class="ltr:ml-2 rtl:mr-2 text-sm text-white/50">{{ isRtl ? 'تذكرني' : 'Remember me' }}</label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-[#1B365D] to-[#1B365D] hover:from-[#1B365D] hover:to-[#1B365D] transition-all duration-300 disabled:opacity-50 shadow-lg shadow-[#1B365D]/20"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ isRtl ? 'جاري الدخول...' : 'Logging in...' }}
                        </span>
                        <span v-else>{{ isRtl ? 'دخول' : 'Login' }}</span>
                    </button>
                </form>
            </div>

            <p class="text-center text-white/20 text-xs mt-6">&copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة دكتوراتو &middot; بوابة مدير الموقع' : 'Doctorato Polyclinic &middot; Webmaster Portal' }}</p>
        </div>
    </div>
</template>
