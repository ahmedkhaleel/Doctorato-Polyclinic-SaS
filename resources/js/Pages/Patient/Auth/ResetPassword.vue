<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const { lp } = usePatientLocale();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const flashError = computed(() => page.props.flash?.error);

function submit() {
    form.post(lp('/reset-password'), {
        preserveScroll: true,
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'"
         class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-[#1a1a2e] via-[#16213e] to-[#1a1a2e]"
         :style="{ fontFamily: isRtl ? '\'Tajawal\', sans-serif' : '\'Poppins\', sans-serif' }">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <img src="/images/logo/logo-light.png" alt="Doctorato" class="mx-auto h-10 w-auto mb-3" />
                <p class="text-gray-400 text-sm uppercase tracking-widest">{{ isRtl ? 'بوابة المريض' : 'Patient Portal' }}</p>
            </div>

            <div class="bg-white/5 backdrop-blur-md rounded-2xl border border-white/10 shadow-2xl p-7">
                <h1 class="text-white text-xl font-bold mb-6">
                    {{ isRtl ? 'تعيين كلمة مرور جديدة' : 'Set a new password' }}
                </h1>

                <div v-if="flashError" class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
                    {{ flashError }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            {{ isRtl ? 'البريد الإلكتروني' : 'Email' }}
                        </label>
                        <input v-model="form.email" type="email" required autocomplete="email" readonly
                            class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white opacity-70 cursor-not-allowed" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            {{ isRtl ? 'كلمة المرور الجديدة' : 'New password' }}
                        </label>
                        <input v-model="form.password" type="password" required autocomplete="new-password" minlength="8"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-[#C4A265] focus:ring-2 focus:ring-[#C4A265]/30 outline-none transition" />
                        <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            {{ isRtl ? 'تأكيد كلمة المرور' : 'Confirm password' }}
                        </label>
                        <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" minlength="8"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-[#C4A265] focus:ring-2 focus:ring-[#C4A265]/30 outline-none transition" />
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full py-2.5 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#D9B985] text-[#1B365D] font-bold transition-all disabled:opacity-50">
                        {{ form.processing
                            ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...')
                            : (isRtl ? 'حفظ كلمة المرور' : 'Reset password') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
