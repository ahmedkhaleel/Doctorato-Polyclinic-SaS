<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';

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

// Entrance animation states
const logoLoaded = ref(false);
const cardLoaded = ref(false);
const fieldsLoaded = ref(false);
const footerLoaded = ref(false);
const showPassword = ref(false);

onMounted(() => {
    setTimeout(() => logoLoaded.value = true, 100);
    setTimeout(() => cardLoaded.value = true, 300);
    setTimeout(() => fieldsLoaded.value = true, 500);
    setTimeout(() => footerLoaded.value = true, 700);
});

function submit() {
    form.post('/doctor/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div :dir="dir" class="login-page min-h-screen flex items-center justify-center px-4 py-8 bg-gradient-to-br from-[#0f0f1a] via-[#1a1a2e] to-[#0f0f1a] relative overflow-hidden" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">

        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Large gradient orbs -->
            <div class="orb-float absolute w-[500px] h-[500px] rounded-full bg-gradient-radial from-[#C4A265]/[0.06] to-transparent -top-48 -right-48"></div>
            <div class="orb-float-reverse absolute w-[400px] h-[400px] rounded-full bg-gradient-radial from-indigo-500/[0.04] to-transparent -bottom-32 -left-32"></div>
            <div class="orb-float-slow absolute w-[300px] h-[300px] rounded-full bg-gradient-radial from-[#C4A265]/[0.03] to-transparent top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>

            <!-- Floating particles -->
            <div class="particle particle-1 absolute w-1.5 h-1.5 rounded-full bg-[#C4A265]/20"></div>
            <div class="particle particle-2 absolute w-1 h-1 rounded-full bg-white/10"></div>
            <div class="particle particle-3 absolute w-2 h-2 rounded-full bg-[#C4A265]/10"></div>
            <div class="particle particle-4 absolute w-1 h-1 rounded-full bg-indigo-400/15"></div>
            <div class="particle particle-5 absolute w-1.5 h-1.5 rounded-full bg-[#C4A265]/15"></div>
            <div class="particle particle-6 absolute w-1 h-1 rounded-full bg-white/[0.07]"></div>

            <!-- Grid pattern overlay -->
            <div class="absolute inset-0 opacity-[0.015]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 48px 48px;"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <!-- Logo Section -->
            <div
                class="text-center mb-8 transition-all duration-1000"
                :class="logoLoaded ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 -translate-y-8 scale-95'"
                :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
            >
                <div class="relative inline-block">
                    <div class="logo-glow absolute inset-0 w-24 h-24 mx-auto rounded-full bg-[#C4A265]/10 blur-xl"></div>
                    <img src="/images/logo/logo.png" alt="AURA Derma Clinic" class="relative mx-auto h-24 w-auto mb-4 drop-shadow-2xl" />
                </div>
                <h1 class="text-4xl font-bold tracking-[0.25em] text-[#C4A265] mb-1.5">AURA</h1>
                <div class="flex items-center justify-center gap-3">
                    <span class="w-8 h-px bg-gradient-to-r from-transparent to-[#C4A265]/40"></span>
                    <p class="text-gray-400 text-xs uppercase tracking-[0.3em] font-medium">{{ isRtl ? 'بوابة الطبيب' : 'Doctor Portal' }}</p>
                    <span class="w-8 h-px bg-gradient-to-l from-transparent to-[#C4A265]/40"></span>
                </div>
            </div>

            <!-- Flash Error -->
            <div
                v-if="flashError"
                class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm text-center backdrop-blur-sm flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                {{ flashError }}
            </div>

            <!-- Login Card -->
            <div
                class="relative bg-white/[0.04] backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/[0.08] overflow-hidden transition-all duration-1000"
                :class="cardLoaded ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-8 scale-95'"
                :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
            >
                <!-- Card top accent line -->
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#C4A265]/40 to-transparent"></div>

                <!-- Card shimmer effect -->
                <div class="card-shimmer absolute inset-0 pointer-events-none"></div>

                <div class="relative p-8 sm:p-10">
                    <h2 class="text-xl font-bold text-white/90 mb-1 text-center">{{ isRtl ? 'مرحبا بك' : 'Welcome Back' }}</h2>
                    <p class="text-sm text-white/40 text-center mb-8">{{ isRtl ? 'سجل دخولك للمتابعة' : 'Sign in to continue' }}</p>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Email -->
                        <div
                            class="transition-all duration-700"
                            :class="fieldsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '0ms' }"
                        >
                            <label for="email" class="block text-sm font-medium text-white/50 mb-2">{{ isRtl ? 'البريد الإلكتروني' : 'Email Address' }}</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 flex items-center pointer-events-none" :class="isRtl ? 'right-4' : 'left-4'">
                                    <svg class="w-4.5 h-4.5 text-white/20 group-focus-within:text-[#C4A265]/60 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    autofocus
                                    class="w-full py-3.5 bg-white/[0.04] border border-white/[0.08] rounded-xl text-white placeholder-white/20 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/40 focus:bg-white/[0.06] text-sm transition-all duration-300"
                                    :class="[form.errors.email ? 'border-red-500/50' : '', isRtl ? 'pr-12 pl-4' : 'pl-12 pr-4']"
                                    placeholder="doctor@aura-derma.com"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-2 text-sm text-red-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div
                            class="transition-all duration-700"
                            :class="fieldsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '80ms' }"
                        >
                            <label for="password" class="block text-sm font-medium text-white/50 mb-2">{{ isRtl ? 'كلمة المرور' : 'Password' }}</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 flex items-center pointer-events-none" :class="isRtl ? 'right-4' : 'left-4'">
                                    <svg class="w-4.5 h-4.5 text-white/20 group-focus-within:text-[#C4A265]/60 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="w-full py-3.5 bg-white/[0.04] border border-white/[0.08] rounded-xl text-white placeholder-white/20 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/40 focus:bg-white/[0.06] text-sm transition-all duration-300"
                                    :class="[form.errors.password ? 'border-red-500/50' : '', isRtl ? 'pr-12 pl-12' : 'pl-12 pr-12']"
                                    :placeholder="isRtl ? 'أدخل كلمة المرور' : 'Enter your password'"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 flex items-center text-white/20 hover:text-white/50 transition-colors"
                                    :class="isRtl ? 'left-4' : 'right-4'"
                                >
                                    <svg v-if="!showPassword" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg v-else class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-2 text-sm text-red-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Remember Me -->
                        <div
                            class="flex items-center transition-all duration-700"
                            :class="fieldsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '160ms' }"
                        >
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input
                                    id="remember"
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-white/20 bg-white/[0.04] text-[#C4A265] focus:ring-[#C4A265]/30 focus:ring-offset-0 transition"
                                />
                                <span class="text-sm text-white/40 group-hover:text-white/60 transition-colors">{{ isRtl ? 'تذكرني' : 'Remember me' }}</span>
                            </label>
                        </div>

                        <!-- Submit -->
                        <div
                            class="transition-all duration-700"
                            :class="fieldsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '240ms' }"
                        >
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="login-btn w-full py-3.5 px-6 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-[#C4A265] to-[#D4B87A] hover:from-[#B8964F] hover:to-[#C4A265] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 relative overflow-hidden group"
                            >
                                <!-- Button shimmer -->
                                <div class="btn-shimmer absolute inset-0 pointer-events-none"></div>
                                <span v-if="form.processing" class="relative flex items-center justify-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    {{ isRtl ? 'جاري التسجيل...' : 'Signing in...' }}
                                </span>
                                <span v-else class="relative flex items-center justify-center gap-2">
                                    {{ isRtl ? 'تسجيل الدخول' : 'Sign In' }}
                                    <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Card bottom accent line -->
                <div class="absolute bottom-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/[0.06] to-transparent"></div>
            </div>

            <!-- Footer -->
            <div
                class="transition-all duration-700"
                :class="footerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
            >
                <p class="text-center text-white/15 text-xs mt-8 tracking-wide">
                    &copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة أورا ديرما' : 'AURA Derma Clinic' }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Floating orb animations */
.orb-float {
    animation: orbFloat 20s ease-in-out infinite;
}
.orb-float-reverse {
    animation: orbFloat 25s ease-in-out infinite reverse;
}
.orb-float-slow {
    animation: orbPulse 15s ease-in-out infinite;
}

@keyframes orbFloat {
    0%, 100% { transform: translate(0, 0); }
    25% { transform: translate(30px, -20px); }
    50% { transform: translate(-20px, 30px); }
    75% { transform: translate(20px, 20px); }
}

@keyframes orbPulse {
    0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.3; }
    50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.6; }
}

/* Floating particles */
.particle {
    animation-timing-function: ease-in-out;
    animation-iteration-count: infinite;
}
.particle-1 {
    top: 15%; left: 10%;
    animation: particleDrift1 12s infinite;
}
.particle-2 {
    top: 25%; right: 15%;
    animation: particleDrift2 15s infinite;
}
.particle-3 {
    bottom: 30%; left: 20%;
    animation: particleDrift3 18s infinite;
}
.particle-4 {
    top: 60%; right: 10%;
    animation: particleDrift1 14s infinite reverse;
}
.particle-5 {
    bottom: 15%; right: 25%;
    animation: particleDrift2 16s infinite;
}
.particle-6 {
    top: 40%; left: 5%;
    animation: particleDrift3 13s infinite reverse;
}

@keyframes particleDrift1 {
    0%, 100% { transform: translate(0, 0); opacity: 0.4; }
    50% { transform: translate(40px, -30px); opacity: 0.8; }
}
@keyframes particleDrift2 {
    0%, 100% { transform: translate(0, 0); opacity: 0.3; }
    33% { transform: translate(-25px, 20px); opacity: 0.7; }
    66% { transform: translate(15px, -35px); opacity: 0.5; }
}
@keyframes particleDrift3 {
    0%, 100% { transform: translate(0, 0); opacity: 0.5; }
    50% { transform: translate(30px, 25px); opacity: 0.9; }
}

/* Logo glow pulse */
.logo-glow {
    animation: glowPulse 4s ease-in-out infinite;
}
@keyframes glowPulse {
    0%, 100% { opacity: 0.4; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.15); }
}

/* Card shimmer */
.card-shimmer {
    background: linear-gradient(
        105deg,
        transparent 40%,
        rgba(196, 162, 101, 0.03) 45%,
        rgba(196, 162, 101, 0.05) 50%,
        rgba(196, 162, 101, 0.03) 55%,
        transparent 60%
    );
    background-size: 200% 100%;
    animation: shimmer 8s ease-in-out infinite;
}

/* Button shimmer */
.btn-shimmer {
    background: linear-gradient(
        105deg,
        transparent 30%,
        rgba(255, 255, 255, 0.1) 45%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.1) 55%,
        transparent 70%
    );
    background-size: 200% 100%;
    animation: shimmer 4s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Button hover lift */
.login-btn:not(:disabled):hover {
    transform: translateY(-1px);
}
.login-btn:not(:disabled):active {
    transform: translateY(0);
}
</style>
