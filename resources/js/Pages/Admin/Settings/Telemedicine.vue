<script setup>
import { ref, computed, reactive } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EncryptedKeyField from '@/Components/Admin/EncryptedKeyField.vue';
import TestConnectionButton from '@/Components/Admin/TestConnectionButton.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    settings: { type: Object, required: true },
    status: { type: Object, required: true },
});

const activeTab = ref('overview');
const savingKey = ref(null);

const tabs = computed(() => [
    { key: 'overview',  labelEn: 'Overview',         labelAr: 'نظرة عامة',      icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { key: 'agora',     labelEn: 'Agora (Video)',    labelAr: 'Agora (الفيديو)',  icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
    { key: 'payment',   labelEn: 'Payment Gateways', labelAr: 'بوابات الدفع',    icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { key: 'reverb',    labelEn: 'Real-time (Reverb)', labelAr: 'البث الحي (Reverb)', icon: 'M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z' },
    { key: 'general',   labelEn: 'General',          labelAr: 'عام',             icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
]);

// Local reactive mutable copies for non-encrypted settings we control in the UI
const localSettings = reactive({ ...props.settings });

function saveSetting(key, value) {
    savingKey.value = key;
    router.post('/admin/settings/telemedicine', { key, value }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => (savingKey.value = null),
    });
}

function toggleBool(key) {
    const newVal = !isTruthy(localSettings[key]);
    localSettings[key] = newVal ? '1' : '0';
    saveSetting(key, newVal ? '1' : '0');
}

function isTruthy(v) {
    return v === true || v === 1 || v === '1' || v === 'true';
}

function toggleModule() {
    const target = !props.status.module_enabled;
    router.post('/admin/settings/telemedicine/toggle', { enabled: target ? 1 : 0 }, {
        preserveScroll: true,
    });
}

function commitText(key) {
    saveSetting(key, localSettings[key] ?? '');
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'إعدادات الاستشارات الأونلاين' : 'Telemedicine Settings'">
        <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#1B365D] to-[#2a4a7a] flex items-center justify-center text-white shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'إعدادات الاستشارات الأونلاين' : 'Telemedicine Settings' }}</h1>
                            <p class="text-sm text-gray-500">{{ isRtl ? 'إعدادات الفيديو، الدفع، والبث المباشر للاستشارات عن بُعد' : 'Configure video, payment, and real-time broadcasting for remote consultations' }}</p>
                        </div>
                    </div>
                </div>
                <Link href="/admin/settings" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isRtl ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'" />
                    </svg>
                    {{ isRtl ? 'العودة للإعدادات' : 'Back to Settings' }}
                </Link>
            </div>

            <!-- Status Dashboard -->
            <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-5">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Module -->
                    <div class="flex items-center gap-2 px-4 py-2 rounded-xl"
                         :class="status.module_enabled ? 'bg-emerald-50 border border-emerald-200' : 'bg-gray-50 border border-gray-200'">
                        <span class="relative flex h-2.5 w-2.5">
                            <span v-if="status.module_enabled" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                                  :class="status.module_enabled ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                        </span>
                        <span class="text-sm font-semibold" :class="status.module_enabled ? 'text-emerald-700' : 'text-gray-600'">
                            {{ isRtl ? 'الوحدة:' : 'Module:' }}
                            {{ status.module_enabled ? (isRtl ? 'مفعّلة' : 'ACTIVE') : (isRtl ? 'معطّلة' : 'INACTIVE') }}
                        </span>
                    </div>

                    <!-- Agora -->
                    <div class="flex items-center gap-2 px-4 py-2 rounded-xl"
                         :class="status.agora_configured ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-gray-50 border border-gray-200 text-gray-500'">
                        <svg v-if="status.agora_configured" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2" /></svg>
                        <span class="text-sm font-semibold">Agora: {{ status.agora_configured ? (isRtl ? 'مُعد' : 'configured') : (isRtl ? 'غير مُعد' : 'not set') }}</span>
                    </div>

                    <!-- Paymob -->
                    <div class="flex items-center gap-2 px-4 py-2 rounded-xl"
                         :class="status.paymob_configured ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-gray-50 border border-gray-200 text-gray-500'">
                        <svg v-if="status.paymob_configured" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2" /></svg>
                        <span class="text-sm font-semibold">Paymob: {{ status.paymob_configured ? (isTruthy(settings.paymob_test_mode) ? (isRtl ? 'تجريبي' : 'test') : (isRtl ? 'مباشر' : 'live')) : (isRtl ? 'غير مفعّل' : 'inactive') }}</span>
                    </div>

                    <!-- Stripe -->
                    <div class="flex items-center gap-2 px-4 py-2 rounded-xl"
                         :class="status.stripe_configured ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-gray-50 border border-gray-200 text-gray-500'">
                        <svg v-if="status.stripe_configured" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2" /></svg>
                        <span class="text-sm font-semibold">Stripe: {{ status.stripe_configured ? (isTruthy(settings.stripe_test_mode) ? (isRtl ? 'تجريبي' : 'test') : (isRtl ? 'مباشر' : 'live')) : (isRtl ? 'غير مفعّل' : 'inactive') }}</span>
                    </div>

                    <!-- Reverb -->
                    <div class="flex items-center gap-2 px-4 py-2 rounded-xl"
                         :class="status.reverb_configured ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-gray-50 border border-gray-200 text-gray-500'">
                        <svg v-if="status.reverb_configured" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2" /></svg>
                        <span class="text-sm font-semibold">Reverb: {{ status.reverb_configured ? (isRtl ? 'متصل' : 'reachable') : (isRtl ? 'غير مُعد' : 'not set') }}</span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex flex-wrap gap-2 border-b border-gray-200">
                <button v-for="tab in tabs" :key="tab.key" type="button" @click="activeTab = tab.key"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-t-lg border-b-2 transition"
                    :class="activeTab === tab.key ? 'border-[#C4A265] text-[#1B365D] bg-[#C4A265]/10' : 'border-transparent text-gray-500 hover:text-[#1B365D]'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                    </svg>
                    {{ isRtl ? tab.labelAr : tab.labelEn }}
                </button>
            </div>

            <!-- OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
                <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">
                                {{ isRtl ? 'حالة الوحدة' : 'Module Status' }}
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ isRtl
                                    ? 'يتطلب تفعيل Agora وبوابة دفع واحدة على الأقل قبل التفعيل.'
                                    : 'Requires Agora + at least one payment gateway to be configured before enabling.' }}
                            </p>
                        </div>
                        <button type="button" @click="toggleModule"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold transition shadow-md"
                            :class="status.module_enabled
                                ? 'bg-red-600 text-white hover:bg-red-700'
                                : 'bg-emerald-600 text-white hover:bg-emerald-700'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <template v-if="status.module_enabled">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </template>
                                <template v-else>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </template>
                            </svg>
                            {{ status.module_enabled
                                ? (isRtl ? 'تعطيل الوحدة' : 'Disable Module')
                                : (isRtl ? 'تفعيل الوحدة' : 'Enable Module') }}
                        </button>
                    </div>
                </div>

                <!-- Quick guide -->
                <div class="rounded-2xl bg-gradient-to-br from-[#1B365D] to-[#2a4a7a] text-white p-6 shadow-lg">
                    <h3 class="text-lg font-bold mb-3">{{ isRtl ? 'دليل التفعيل السريع' : 'Quick Setup Guide' }}</h3>
                    <ol class="space-y-2 text-sm">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                :class="status.agora_configured ? 'bg-emerald-400 text-emerald-900' : 'bg-white/20'">1</span>
                            <span>{{ isRtl ? 'قم بتهيئة بيانات Agora (App ID والشهادة)' : 'Configure Agora credentials (App ID and Certificate)' }}</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                :class="(status.paymob_configured || status.stripe_configured) ? 'bg-emerald-400 text-emerald-900' : 'bg-white/20'">2</span>
                            <span>{{ isRtl ? 'فعّل بوابة دفع واحدة على الأقل (Paymob أو Stripe)' : 'Enable at least one payment gateway (Paymob or Stripe)' }}</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                :class="status.reverb_configured ? 'bg-emerald-400 text-emerald-900' : 'bg-white/20'">3</span>
                            <span>{{ isRtl ? '(اختياري) قم بإعداد Reverb لغرف الانتظار الحية' : '(Optional) Configure Reverb for live waiting rooms' }}</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                :class="status.module_enabled ? 'bg-emerald-400 text-emerald-900' : 'bg-white/20'">4</span>
                            <span>{{ isRtl ? 'قم بتفعيل الوحدة من بطاقة الحالة أعلاه' : 'Enable the module from the status card above' }}</span>
                        </li>
                    </ol>
                </div>
            </div>

            <!-- AGORA -->
            <div v-if="activeTab === 'agora'" class="space-y-6">
                <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Agora</h2>
                            <p class="text-sm text-gray-500">{{ isRtl ? 'خدمة بث الفيديو للاستشارات' : 'Video streaming service for consultations' }}</p>
                        </div>
                        <a href="https://console.agora.io/" target="_blank" class="text-xs font-semibold text-[#1B365D] hover:underline">
                            {{ isRtl ? 'لوحة تحكم Agora' : 'Agora Console' }} ↗
                        </a>
                    </div>

                    <div class="grid gap-5">
                        <EncryptedKeyField
                            :label="isRtl ? 'App ID' : 'App ID'"
                            setting-key="agora_app_id"
                            :is-set="settings.agora_app_id?.is_set"
                            :masked="settings.agora_app_id?.masked"
                            :description="isRtl ? 'معرّف تطبيق Agora الفريد' : 'Your unique Agora application ID'"
                        />
                        <EncryptedKeyField
                            :label="isRtl ? 'App Certificate' : 'App Certificate'"
                            setting-key="agora_app_certificate"
                            :is-set="settings.agora_app_certificate?.is_set"
                            :masked="settings.agora_app_certificate?.masked"
                            :description="isRtl ? 'شهادة التطبيق المستخدمة لإنشاء الرموز' : 'Primary certificate used to generate tokens'"
                        />

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                {{ isRtl ? 'مدة صلاحية الرمز (ثوانٍ)' : 'Token Expiry (seconds)' }}
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" v-model="localSettings.agora_token_expiry_seconds" @blur="commitText('agora_token_expiry_seconds')"
                                    class="flex-1 px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                                <span class="text-xs text-gray-500">{{ isRtl ? 'الافتراضي 7200 (ساعتان)' : 'default 7200 (2 hours)' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <TestConnectionButton endpoint="/admin/settings/telemedicine/test-agora"
                            :label="isRtl ? 'اختبار اتصال Agora' : 'Test Agora Connection'" />
                    </div>
                </div>
            </div>

            <!-- PAYMENT -->
            <div v-if="activeTab === 'payment'" class="space-y-6">
                <!-- Active gateway selector -->
                <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">{{ isRtl ? 'بوابة الدفع النشطة' : 'Active Payment Gateway' }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <button type="button" @click="() => { localSettings.payment_active_gateway = 'paymob'; saveSetting('payment_active_gateway', 'paymob'); }"
                            class="rounded-xl border-2 p-4 text-start transition"
                            :class="localSettings.payment_active_gateway === 'paymob' ? 'border-[#C4A265] bg-[#C4A265]/5' : 'border-gray-200 hover:border-gray-300'">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-gray-900">Paymob</div>
                                    <div class="text-xs text-gray-500">{{ isRtl ? 'الأفضل في مصر — الدفع عبر البطاقات والمحافظ' : 'Best for Egypt — cards & wallets' }}</div>
                                </div>
                                <svg v-if="localSettings.payment_active_gateway === 'paymob'" class="w-5 h-5 text-[#C4A265]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
                                </svg>
                            </div>
                        </button>
                        <button type="button" @click="() => { localSettings.payment_active_gateway = 'stripe'; saveSetting('payment_active_gateway', 'stripe'); }"
                            class="rounded-xl border-2 p-4 text-start transition"
                            :class="localSettings.payment_active_gateway === 'stripe' ? 'border-[#C4A265] bg-[#C4A265]/5' : 'border-gray-200 hover:border-gray-300'">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-gray-900">Stripe</div>
                                    <div class="text-xs text-gray-500">{{ isRtl ? 'عالمي — بطاقات دولية' : 'Global — international cards' }}</div>
                                </div>
                                <svg v-if="localSettings.payment_active_gateway === 'stripe'" class="w-5 h-5 text-[#C4A265]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Paymob -->
                <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Paymob</h2>
                            <p class="text-sm text-gray-500">{{ isRtl ? 'إعدادات Paymob' : 'Paymob configuration' }}</p>
                        </div>
                        <a href="https://accept.paymob.com/portal2/en/home" target="_blank" class="text-xs font-semibold text-[#1B365D] hover:underline">
                            {{ isRtl ? 'بوابة Paymob' : 'Paymob Dashboard' }} ↗
                        </a>
                    </div>

                    <div class="flex items-center gap-6 mb-5">
                        <!-- Enabled toggle -->
                        <label class="flex items-center gap-2 cursor-pointer">
                            <button type="button" @click="toggleBool('paymob_enabled')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                                :class="isTruthy(localSettings.paymob_enabled) ? 'bg-emerald-500' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                    :class="isTruthy(localSettings.paymob_enabled) ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                            <span class="text-sm font-semibold text-gray-700">{{ isRtl ? 'مفعّل' : 'Enabled' }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <button type="button" @click="toggleBool('paymob_test_mode')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                                :class="isTruthy(localSettings.paymob_test_mode) ? 'bg-amber-500' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                    :class="isTruthy(localSettings.paymob_test_mode) ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                            <span class="text-sm font-semibold text-gray-700">{{ isRtl ? 'وضع الاختبار' : 'Test Mode' }}</span>
                        </label>
                    </div>

                    <div class="grid gap-4">
                        <EncryptedKeyField label="API Key" setting-key="paymob_api_key"
                            :is-set="settings.paymob_api_key?.is_set" :masked="settings.paymob_api_key?.masked" />
                        <EncryptedKeyField label="HMAC Secret" setting-key="paymob_hmac_secret"
                            :is-set="settings.paymob_hmac_secret?.is_set" :masked="settings.paymob_hmac_secret?.masked" />
                        <EncryptedKeyField label="Iframe ID" setting-key="paymob_iframe_id"
                            :is-set="settings.paymob_iframe_id?.is_set" :masked="settings.paymob_iframe_id?.masked" />
                        <EncryptedKeyField label="Integration ID" setting-key="paymob_integration_id"
                            :is-set="settings.paymob_integration_id?.is_set" :masked="settings.paymob_integration_id?.masked" />
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <TestConnectionButton endpoint="/admin/settings/telemedicine/test-paymob"
                            :label="isRtl ? 'اختبار اتصال Paymob' : 'Test Paymob Connection'" />
                    </div>
                </div>

                <!-- Stripe -->
                <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Stripe</h2>
                            <p class="text-sm text-gray-500">{{ isRtl ? 'إعدادات Stripe' : 'Stripe configuration' }}</p>
                        </div>
                        <a href="https://dashboard.stripe.com/" target="_blank" class="text-xs font-semibold text-[#1B365D] hover:underline">
                            Stripe Dashboard ↗
                        </a>
                    </div>

                    <div class="flex items-center gap-6 mb-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <button type="button" @click="toggleBool('stripe_enabled')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                                :class="isTruthy(localSettings.stripe_enabled) ? 'bg-emerald-500' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                    :class="isTruthy(localSettings.stripe_enabled) ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                            <span class="text-sm font-semibold text-gray-700">{{ isRtl ? 'مفعّل' : 'Enabled' }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <button type="button" @click="toggleBool('stripe_test_mode')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                                :class="isTruthy(localSettings.stripe_test_mode) ? 'bg-amber-500' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                    :class="isTruthy(localSettings.stripe_test_mode) ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                            <span class="text-sm font-semibold text-gray-700">{{ isRtl ? 'وضع الاختبار' : 'Test Mode' }}</span>
                        </label>
                    </div>

                    <div class="grid gap-4">
                        <EncryptedKeyField label="Secret Key" setting-key="stripe_secret_key"
                            :is-set="settings.stripe_secret_key?.is_set" :masked="settings.stripe_secret_key?.masked"
                            placeholder="sk_live_... or sk_test_..." />
                        <EncryptedKeyField label="Publishable Key" setting-key="stripe_publishable_key"
                            :is-set="settings.stripe_publishable_key?.is_set" :masked="settings.stripe_publishable_key?.masked"
                            placeholder="pk_live_... or pk_test_..." />
                        <EncryptedKeyField label="Webhook Secret" setting-key="stripe_webhook_secret"
                            :is-set="settings.stripe_webhook_secret?.is_set" :masked="settings.stripe_webhook_secret?.masked"
                            placeholder="whsec_..." />
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <TestConnectionButton endpoint="/admin/settings/telemedicine/test-stripe"
                            :label="isRtl ? 'اختبار اتصال Stripe' : 'Test Stripe Connection'" />
                    </div>
                </div>
            </div>

            <!-- REVERB -->
            <div v-if="activeTab === 'reverb'" class="space-y-6">
                <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Reverb</h2>
                            <p class="text-sm text-gray-500">{{ isRtl ? 'خادم WebSocket للإشعارات المباشرة' : 'WebSocket server for real-time notifications' }}</p>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">App ID</label>
                            <input type="text" v-model="localSettings.reverb_app_id" @blur="commitText('reverb_app_id')"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Host</label>
                            <input type="text" v-model="localSettings.reverb_host" @blur="commitText('reverb_host')"
                                placeholder="reverb.doctorato.net"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Port</label>
                            <input type="number" v-model="localSettings.reverb_port" @blur="commitText('reverb_port')"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Scheme</label>
                            <select v-model="localSettings.reverb_scheme" @change="commitText('reverb_scheme')"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30">
                                <option value="https">https</option>
                                <option value="http">http</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <EncryptedKeyField label="App Key" setting-key="reverb_app_key"
                                :is-set="settings.reverb_app_key?.is_set" :masked="settings.reverb_app_key?.masked" />
                        </div>
                        <div class="md:col-span-2">
                            <EncryptedKeyField label="App Secret" setting-key="reverb_app_secret"
                                :is-set="settings.reverb_app_secret?.is_set" :masked="settings.reverb_app_secret?.masked" />
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <TestConnectionButton endpoint="/admin/settings/telemedicine/test-reverb"
                            :label="isRtl ? 'اختبار اتصال Reverb' : 'Test Reverb Connection'" />
                    </div>
                </div>
            </div>

            <!-- GENERAL -->
            <div v-if="activeTab === 'general'" class="space-y-6">
                <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-5">{{ isRtl ? 'إعدادات عامة للاستشارات' : 'Consultation General Settings' }}</h2>

                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                {{ isRtl ? 'نافذة الانضمام (دقائق)' : 'Join Window (minutes)' }}
                            </label>
                            <input type="number" v-model="localSettings.telemedicine_join_window_minutes" @blur="commitText('telemedicine_join_window_minutes')"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                            <p class="mt-1 text-xs text-gray-500">{{ isRtl ? 'الوقت قبل الموعد الذي يمكن خلاله الانضمام' : 'How early patients/doctors can join before the scheduled time' }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                {{ isRtl ? 'مهلة الانتظار (دقائق)' : 'Wait Timeout (minutes)' }}
                            </label>
                            <input type="number" v-model="localSettings.telemedicine_wait_timeout_minutes" @blur="commitText('telemedicine_wait_timeout_minutes')"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                            <p class="mt-1 text-xs text-gray-500">{{ isRtl ? 'مدة انتظار الطرف الثاني قبل إلغاء الجلسة' : 'How long to wait for the other party before cancelling' }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                {{ isRtl ? 'نافذة الإلغاء (ساعات)' : 'Cancellation Window (hours)' }}
                            </label>
                            <input type="number" v-model="localSettings.telemedicine_cancellation_hours" @blur="commitText('telemedicine_cancellation_hours')"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                            <p class="mt-1 text-xs text-gray-500">{{ isRtl ? 'عدد الساعات قبل الموعد التي يسمح فيها بالإلغاء المجاني' : 'Hours before appointment that allow free cancellation' }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                {{ isRtl ? 'نسبة الاسترداد (%)' : 'Refund Percentage (%)' }}
                            </label>
                            <input type="number" min="0" max="100" v-model="localSettings.telemedicine_refund_percentage" @blur="commitText('telemedicine_refund_percentage')"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                            <p class="mt-1 text-xs text-gray-500">{{ isRtl ? 'النسبة المستردة عند الإلغاء ضمن النافذة' : 'Refund percentage for timely cancellations' }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                {{ isRtl ? 'رسوم إضافية على الاستشارة الأونلاين (%)' : 'Extra Fee on Online Consultation (%)' }}
                            </label>
                            <input type="number" min="0" v-model="localSettings.telemedicine_extra_fee_percentage" @blur="commitText('telemedicine_extra_fee_percentage')"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                            <p class="mt-1 text-xs text-gray-500">{{ isRtl ? 'نسبة تضاف فوق رسوم الطبيب لتغطية رسوم المنصة' : 'Percentage added on top of doctor fee for platform costs' }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
