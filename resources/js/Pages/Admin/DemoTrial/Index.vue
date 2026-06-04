<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    settings: { type: Object, default: () => ({}) },
    appUrl: { type: String, default: '' },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

// ── Trial settings form ───────────────────────────────
const settingsForm = useForm({
    trial_days: props.settings.trial_days ?? 14,
    trial_contact_url: props.settings.trial_contact_url ?? 'https://doctorato.com/contact',
});
const saveSettings = () => settingsForm.post('/admin/demo-trial/settings', { preserveScroll: true });

// ── Extend trials form ────────────────────────────────
const extendForm = useForm({ days: props.settings.trial_days ?? 14 });
const extendOpen = ref(false);
const doExtend = () => extendForm.post('/admin/demo-trial/extend', {
    preserveScroll: true,
    onSuccess: () => { extendOpen.value = false; },
});

// ── Reset password form ───────────────────────────────
const pwForm = useForm({ password: '', password_confirmation: '' });
const pwOpen = ref(false);
const doResetPassword = () => pwForm.post('/admin/demo-trial/reset-password', {
    preserveScroll: true,
    onSuccess: () => { pwForm.reset(); pwOpen.value = false; },
});

// ── Copy-to-clipboard helper ──────────────────────────
const copied = ref(null);
const copy = (text, key) => {
    navigator.clipboard?.writeText(text).then(() => {
        copied.value = key;
        setTimeout(() => { if (copied.value === key) copied.value = null; }, 1600);
    });
};

const statusClass = (a) => {
    if (!a.is_active) return 'bg-rose-50 text-rose-700 border-rose-200';
    if (a.trial_expired) return 'bg-rose-50 text-rose-700 border-rose-200';
    if (a.trial_days_left <= 3) return 'bg-amber-50 text-amber-700 border-amber-200';
    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
};
const statusLabel = (a) => {
    if (!a.is_active) return t('معطّل', 'Disabled');
    if (a.trial_expired) return t('انتهت', 'Expired');
    return t(`${a.trial_days_left} يوم متبقٍ`, `${a.trial_days_left} days left`);
};
</script>

<template>
    <AdminLayout :title="t('الديمو والفترة التجريبية', 'Demo & Trial')">
        <div class="max-w-5xl mx-auto px-4 py-6 space-y-6 dt-fade">

            <!-- Header -->
            <header class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold text-[#1B365D]">{{ t('الديمو والفترة التجريبية', 'Demo & Trial') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ t('حسابات العرض التوضيحي وروابط الدخول وإدارة الفترة التجريبية.', 'Demo accounts, login links, and trial-period management.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="extendOpen = true" :title="t('تمديد التجربة', 'Extend trial')"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-[#1B365D] text-white text-sm hover:opacity-90 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="hidden sm:inline">{{ t('تمديد التجربة', 'Extend trial') }}</span>
                    </button>
                    <button type="button" @click="pwOpen = true" :title="t('تغيير كلمة المرور', 'Change password')"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-[#C4A265] text-white text-sm hover:opacity-90 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 7a2 2 0 012 2m4-2a6 6 0 01-7.743 5.743L11 14H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                        <span class="hidden sm:inline">{{ t('كلمة مرور الديمو', 'Demo password') }}</span>
                    </button>
                </div>
            </header>

            <!-- Demo accounts -->
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/60">
                    <h2 class="font-semibold text-gray-800">{{ t('حسابات الديمو', 'Demo accounts') }}</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <div v-for="a in accounts" :key="a.id"
                        class="dt-row p-4 sm:flex sm:items-center sm:justify-between gap-4 hover:bg-gray-50/60 transition">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-gray-800">{{ a.name }}</span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-[#1B365D]/10 text-[#1B365D]">
                                    {{ isRtl ? a.panel_label_ar : a.panel_label_en }}
                                </span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full border" :class="statusClass(a)">
                                    {{ statusLabel(a) }}
                                </span>
                            </div>
                            <!-- Email row with copy -->
                            <div class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <code class="px-2 py-1 rounded bg-gray-100 text-gray-700 truncate" dir="ltr">{{ a.email }}</code>
                                <button type="button" @click="copy(a.email, 'email-' + a.id)" :title="t('نسخ البريد', 'Copy email')"
                                    class="text-gray-400 hover:text-[#1B365D] transition shrink-0">
                                    <svg v-if="copied === 'email-' + a.id" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                </button>
                            </div>
                            <!-- Login link row with copy + open -->
                            <div class="mt-2 flex items-center gap-2 text-sm">
                                <code class="px-2 py-1 rounded bg-gray-100 text-gray-600 truncate max-w-[260px]" dir="ltr">{{ a.login_url }}</code>
                                <button type="button" @click="copy(a.login_url, 'url-' + a.id)" :title="t('نسخ الرابط', 'Copy link')"
                                    class="text-gray-400 hover:text-[#1B365D] transition shrink-0">
                                    <svg v-if="copied === 'url-' + a.id" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.656-2.828a4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5" /></svg>
                                </button>
                                <a :href="a.login_url" target="_blank" rel="noopener" :title="t('فتح', 'Open')"
                                    class="text-gray-400 hover:text-[#C4A265] transition shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <p v-if="!accounts.length" class="p-6 text-center text-sm text-gray-400">
                        {{ t('لا توجد حسابات ديمو بعد. شغّل DemoUserSeeder.', 'No demo accounts yet. Run the DemoUserSeeder.') }}
                    </p>
                </div>
                <div class="px-5 py-3 bg-amber-50/60 border-t border-amber-100 text-xs text-amber-800">
                    {{ t('كلمة المرور موحّدة لكل الحسابات. غيّرها من زر «كلمة مرور الديمو». تظهر هنا الأيام المتبقية لكل حساب.',
                        'All accounts share one password. Change it via "Demo password". Days remaining per account shown above.') }}
                </div>
            </section>

            <!-- Trial settings -->
            <section class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-1">{{ t('إعدادات الفترة التجريبية', 'Trial settings') }}</h2>
                <p class="text-xs text-gray-500 mb-4">
                    {{ t('تطبَّق على الحسابات التي تُنشأ بعد الحفظ. لتعديل الحسابات الحالية استخدم «تمديد التجربة».',
                        'Applies to accounts created after saving. Use "Extend trial" for existing accounts.') }}
                </p>
                <form @submit.prevent="saveSettings" class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('مدة التجربة (أيام)', 'Trial duration (days)') }}</label>
                        <input v-model.number="settingsForm.trial_days" type="number" min="1" max="3650"
                            class="w-full rounded-lg border-gray-300 focus:border-[#1B365D] focus:ring-[#1B365D] text-sm" />
                        <p v-if="settingsForm.errors.trial_days" class="text-xs text-rose-600 mt-1">{{ settingsForm.errors.trial_days }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('رابط التواصل عند انتهاء التجربة', 'Contact URL after expiry') }}</label>
                        <input v-model="settingsForm.trial_contact_url" type="url" dir="ltr"
                            class="w-full rounded-lg border-gray-300 focus:border-[#1B365D] focus:ring-[#1B365D] text-sm" />
                        <p v-if="settingsForm.errors.trial_contact_url" class="text-xs text-rose-600 mt-1">{{ settingsForm.errors.trial_contact_url }}</p>
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" :disabled="settingsForm.processing"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#1B365D] text-white text-sm hover:opacity-90 transition disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M5 13l4 4L19 7" /></svg>
                            {{ t('حفظ', 'Save') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <!-- Extend trial modal -->
        <Transition name="dt-modal">
            <div v-if="extendOpen" v-focus-trap="() => (extendOpen = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="extendOpen = false">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 dt-pop">
                    <h3 class="text-lg font-bold text-[#1B365D] mb-1">{{ t('تمديد فترة التجربة', 'Extend trial period') }}</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        {{ t('يُعاد ضبط تاريخ الانتهاء لكل حسابات الديمو ويُعاد تفعيلها.', 'Resets the expiry date for all demo accounts and re-activates them.') }}
                    </p>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('عدد الأيام من الآن', 'Days from now') }}</label>
                    <input v-model.number="extendForm.days" type="number" min="1" max="3650"
                        class="w-full rounded-lg border-gray-300 focus:border-[#1B365D] focus:ring-[#1B365D] text-sm" />
                    <p v-if="extendForm.errors.days" class="text-xs text-rose-600 mt-1">{{ extendForm.errors.days }}</p>
                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" @click="extendOpen = false" class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition">{{ t('إلغاء', 'Cancel') }}</button>
                        <button type="button" @click="doExtend" :disabled="extendForm.processing"
                            class="px-4 py-2 rounded-lg bg-[#1B365D] text-white text-sm hover:opacity-90 transition disabled:opacity-50">{{ t('تمديد', 'Extend') }}</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Reset password modal -->
        <Transition name="dt-modal">
            <div v-if="pwOpen" v-focus-trap="() => (pwOpen = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="pwOpen = false">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 dt-pop">
                    <h3 class="text-lg font-bold text-[#1B365D] mb-1">{{ t('تغيير كلمة مرور الديمو', 'Change demo password') }}</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        {{ t('كلمة مرور موحّدة جديدة لكل حسابات الديمو الأربعة، وتُطبَّق فورًا.', 'A new shared password for all demo accounts, applied immediately.') }}
                    </p>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('كلمة المرور الجديدة', 'New password') }}</label>
                    <input v-model="pwForm.password" type="text" dir="ltr" autocomplete="off"
                        class="w-full rounded-lg border-gray-300 focus:border-[#C4A265] focus:ring-[#C4A265] text-sm mb-3" />
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('تأكيد كلمة المرور', 'Confirm password') }}</label>
                    <input v-model="pwForm.password_confirmation" type="text" dir="ltr" autocomplete="off"
                        class="w-full rounded-lg border-gray-300 focus:border-[#C4A265] focus:ring-[#C4A265] text-sm" />
                    <p v-if="pwForm.errors.password" class="text-xs text-rose-600 mt-1">{{ pwForm.errors.password }}</p>
                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" @click="pwOpen = false" class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition">{{ t('إلغاء', 'Cancel') }}</button>
                        <button type="button" @click="doResetPassword" :disabled="pwForm.processing"
                            class="px-4 py-2 rounded-lg bg-[#C4A265] text-white text-sm hover:opacity-90 transition disabled:opacity-50">{{ t('تطبيق', 'Apply') }}</button>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>

<style scoped>
.dt-fade { animation: dtFade .4s cubic-bezier(.22,.61,.36,1) both; }
.dt-row { animation: dtRow .35s ease both; }
@keyframes dtFade { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
@keyframes dtRow { from { opacity: 0; transform: translateX(8px); } to { opacity: 1; transform: none; } }
.dt-pop { animation: dtPop .25s cubic-bezier(.22,.61,.36,1) both; }
@keyframes dtPop { from { opacity: 0; transform: scale(.96) translateY(6px); } to { opacity: 1; transform: none; } }
.dt-modal-enter-active, .dt-modal-leave-active { transition: opacity .2s ease; }
.dt-modal-enter-from, .dt-modal-leave-to { opacity: 0; }
@media (prefers-reduced-motion: reduce) {
    .dt-fade, .dt-row, .dt-pop { animation: none !important; }
    .dt-modal-enter-active, .dt-modal-leave-active { transition: none !important; }
}
</style>
