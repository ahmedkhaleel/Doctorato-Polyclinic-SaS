<script setup>
import { computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
}

function switchLocale() {
    const newLocale = locale.value === 'ar' ? 'en' : 'ar';
    router.post('/admin/switch-locale-public', { locale: newLocale }, { preserveScroll: true });
}
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'" class="min-h-screen flex items-center justify-center px-4 relative" style="background-color: #3A3A3A;">

        <!-- Language Toggle -->
        <button
            @click="switchLocale"
            class="absolute top-5 flex items-center gap-2 px-3.5 py-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm text-gray-300 hover:text-white text-sm font-medium transition-all duration-200 border border-white/10 hover:border-white/20"
            :class="isRtl ? 'left-5' : 'right-5'"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ locale === 'ar' ? 'English' : 'العربية' }}</span>
        </button>

        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="/images/logo/logo-light.png" alt="Doctorato Polyclinic" class="mx-auto h-12 w-auto mb-4" />
                <p class="text-gray-400 text-sm uppercase tracking-widest">{{ isRtl ? 'لوحة الإدارة' : 'Admin Panel' }}</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-lg shadow-xl p-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">{{ $t('a_login_title') }}</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Username / Email -->
                    <div>
                        <label for="login" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_login_username_or_email') }}</label>
                        <input
                            id="login"
                            v-model="form.login"
                            type="text"
                            required
                            autofocus
                            class="doctorato-input w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent text-sm transition"
                            :class="form.errors.login ? 'border-red-500 focus:ring-[#C4A265]/30' : 'focus:ring-[#C4A265]/30'"
                            :placeholder="$t('a_login_username_or_email_placeholder')"
                        />
                        <p v-if="form.errors.login" class="mt-1 text-sm text-red-600">{{ form.errors.login }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_login_password') }}</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="doctorato-input w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent text-sm transition"
                            :class="form.errors.password ? 'border-red-500 focus:ring-[#C4A265]/30' : 'focus:ring-[#C4A265]/30'"
                            :placeholder="$t('a_enter_password')"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 focus:ring-2"
                            style="color: #C4A265;"
                        />
                        <label for="remember" :class="isRtl ? 'mr-2' : 'ml-2'" class="text-sm text-gray-600">{{ $t('a_login_remember') }}</label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 px-4 rounded-lg text-white font-medium text-sm transition-all duration-200 disabled:opacity-50"
                        style="background-color: #C4A265;"
                        @mouseover="$event.target.style.backgroundColor='#A68B52'"
                        @mouseleave="$event.target.style.backgroundColor='#C4A265'"
                    >
                        <span v-if="form.processing">{{ $t('a_signing_in') }}</span>
                        <span v-else>{{ $t('a_login_button') }}</span>
                    </button>

                    <!-- General error -->
                    <p v-if="form.errors.general" class="text-sm text-red-600 text-center">{{ form.errors.general }}</p>
                </form>
            </div>

            <p class="text-center text-gray-500 text-xs mt-6">Doctorato Polyclinic Admin Panel</p>
        </div>
    </div>
</template>
