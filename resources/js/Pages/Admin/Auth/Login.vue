<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

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
</script>

<template>
    <div dir="ltr" class="min-h-screen flex items-center justify-center px-4" style="background-color: #3A3A3A;">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="/images/logo/logo.png" alt="AURA Derma Clinic" class="mx-auto h-20 w-auto mb-3" />
                <h1 class="text-4xl font-bold tracking-widest" style="color: #C4A265;">AURA</h1>
                <p class="text-gray-400 text-sm uppercase tracking-widest mt-1">Derma Clinic</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-lg shadow-xl p-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-1 text-center">Admin Login</h2>
                <p class="text-sm text-gray-500 mb-6 text-center">تسجيل دخول الإدارة</p>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Username / Email -->
                    <div>
                        <label for="login" class="block text-sm font-medium text-gray-700 mb-1">
                            Username or Email
                            <span class="text-gray-400 font-normal">/ اسم المستخدم أو البريد الإلكتروني</span>
                        </label>
                        <input
                            id="login"
                            v-model="form.login"
                            type="text"
                            required
                            autofocus
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent text-sm transition"
                            :class="form.errors.login ? 'border-red-500 focus:ring-red-200' : 'focus:ring-yellow-200'"
                            placeholder="admin@aura-derma.com or username"
                        />
                        <p v-if="form.errors.login" class="mt-1 text-sm text-red-600">{{ form.errors.login }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                            <span class="text-gray-400 font-normal">/ كلمة المرور</span>
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent text-sm transition"
                            :class="form.errors.password ? 'border-red-500 focus:ring-red-200' : 'focus:ring-yellow-200'"
                            placeholder="Enter your password / أدخل كلمة المرور"
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
                        <label for="remember" class="ml-2 text-sm text-gray-600">Remember Me / تذكرني</label>
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
                        <span v-if="form.processing">Signing in... / جارٍ تسجيل الدخول...</span>
                        <span v-else>Sign In / تسجيل الدخول</span>
                    </button>

                    <!-- General error -->
                    <p v-if="form.errors.general" class="text-sm text-red-600 text-center">{{ form.errors.general }}</p>
                </form>
            </div>

            <p class="text-center text-gray-500 text-xs mt-6">AURA Derma Clinic Admin Panel</p>
        </div>
    </div>
</template>
